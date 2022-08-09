<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranslateRequest;
use Illuminate\Http\Request;
use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class TranslateController extends Controller
{

    public function translate(TranslateRequest $request)
    {
        $content = Yaml::parse($request->file('file')->get());
        $content = Arr::dot($content);

        $content = array_map(function ($string) {
            $tokens = [];

            $index = 0;
            $string = preg_replace_callback('/%\{.+?\}/', function ($token) use (&$index, &$tokens) {
                $key = '%{' . $index++ . '}';
                $tokens[$key] = current($token);

                return $key;
            }, $string);

            return [
                'string' => $string,
                'tokens' => $tokens,
            ];
        }, $content);

        $translateStrings = array_column($content, 'string');

        $translate = new TranslateClient([
            'key' => $request->key
        ]);

        $results = $translate->translateBatch($translateStrings, [
                'target' => $request->target,
                'source' => $request->source
            ]
        );

        $content = array_map(function ($item) use ($results) {
            // todo: optimize e.g. hash keys
            foreach ($results as $translation) {
                if ($item['string'] == $translation['input']) {
                    if (count($item['tokens'])) {
                        return str_replace(
                            array_keys($item['tokens']),
                            array_values($item['tokens']),
                            $translation['text']
                        );
                    }

                    return $translation['text'];
                }
            }
            // return original string?
        }, $content);

        $content = Arr::undot($content);
        $content = Yaml::dump($content, PHP_INT_MAX);

        $name = crc32($content);

        Storage::disk('local')->put($name, $content);

        return response()->json([
            'url' => route('download', [
                'name' => $name,
                'as' => $request->name
            ])
        ]);

        // TODO: make frontend
//        return redirect()->route('download', [
//            'name' => $name,
//            'as' => $request->name
//        ]);
    }

    public function download(Request $request)
    {
        return response()->download(
            Storage::disk('local')->path($request->name),
            $request->as
        );
    }
}

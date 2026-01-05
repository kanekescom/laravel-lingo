<?php

use Kanekescom\Lingo\Lingo;
use Kanekescom\Lingo\LingoBuilder;

describe('LingoBuilder chainable methods', function () {
    it('can be created via Lingo::make', function () {
        $builder = Lingo::make(['Hello' => 'Halo']);

        expect($builder)->toBeInstanceOf(LingoBuilder::class);
        expect($builder->get())->toBe(['Hello' => 'Halo']);
    });

    it('can be created via static make method', function () {
        $builder = LingoBuilder::make(['World' => 'Dunia']);

        expect($builder)->toBeInstanceOf(LingoBuilder::class);
        expect($builder->get())->toBe(['World' => 'Dunia']);
    });

    it('can sort keys ascending', function () {
        $builder = LingoBuilder::make(['z' => 'last', 'a' => 'first', 'm' => 'middle']);

        $result = $builder->sortKeys()->get();
        $keys = array_keys($result);

        expect($keys[0])->toBe('a');
        expect($keys[2])->toBe('z');
    });

    it('can sort keys descending', function () {
        $builder = LingoBuilder::make(['z' => 'last', 'a' => 'first']);

        $result = $builder->sortKeys(false)->get();
        $keys = array_keys($result);

        expect($keys[0])->toBe('z');
        expect($keys[1])->toBe('a');
    });

    it('can clean translations', function () {
        $builder = LingoBuilder::make([
            'valid' => 'value',
            'empty' => '',
            'another' => 'test',
        ]);

        $result = $builder->clean()->get();

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('valid');
    });

    it('can add missing keys', function () {
        $builder = LingoBuilder::make(['Hello' => 'Halo']);

        $result = $builder->addMissing(['Hello', 'World', 'Goodbye'])->get();

        expect($result)->toHaveCount(3);
        expect($result['World'])->toBe('World');
        expect($result['Goodbye'])->toBe('Goodbye');
    });

    it('can remove unused keys', function () {
        $builder = LingoBuilder::make([
            'Hello' => 'Halo',
            'World' => 'Dunia',
            'Unused' => 'Tidak Dipakai',
        ]);

        $result = $builder->removeUnused(['Hello', 'World'])->get();

        expect($result)->toHaveCount(2);
        expect($result)->not->toHaveKey('Unused');
    });

    it('can remove empty values', function () {
        $builder = LingoBuilder::make([
            'valid' => 'value',
            'empty' => '',
            'another' => 'test',
        ]);

        $result = $builder->removeEmpty()->get();

        expect($result)->toHaveCount(2);
    });

    it('can filter to only untranslated', function () {
        $builder = LingoBuilder::make([
            'Hello' => 'Halo',
            'World' => 'World',
        ]);

        $result = $builder->onlyUntranslated()->get();

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('World');
    });

    it('can filter to only translated', function () {
        $builder = LingoBuilder::make([
            'Hello' => 'Halo',
            'World' => 'World',
        ]);

        $result = $builder->onlyTranslated()->get();

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('Hello');
    });

    it('can merge translations', function () {
        $builder = LingoBuilder::make(['Hello' => 'Halo']);

        $result = $builder->merge(['World' => 'Dunia'])->get();

        expect($result)->toHaveCount(2);
        expect($result['World'])->toBe('Dunia');
    });

    it('can transform translations with callback', function () {
        $builder = LingoBuilder::make(['Hello' => 'Halo']);

        $result = $builder->transform(function ($translations) {
            return array_map('strtoupper', $translations);
        })->get();

        expect($result['Hello'])->toBe('HALO');
    });

    it('can tap translations without modifying', function () {
        $tapped = null;
        $builder = LingoBuilder::make(['Hello' => 'Halo']);

        $builder->tap(function ($translations) use (&$tapped) {
            $tapped = $translations;
        });

        expect($tapped)->toBe(['Hello' => 'Halo']);
    });

    it('can get statistics', function () {
        $builder = LingoBuilder::make([
            'Hello' => 'Halo',
            'World' => 'World',
        ]);

        $stats = $builder->stats();

        expect($stats['total'])->toBe(2);
        expect($stats['translated'])->toBe(1);
        expect($stats['untranslated'])->toBe(1);
    });

    it('can count translations', function () {
        $builder = LingoBuilder::make(['a' => 'b', 'c' => 'd']);

        expect($builder->count())->toBe(2);
    });

    it('can check if empty', function () {
        expect(LingoBuilder::make([])->isEmpty())->toBeTrue();
        expect(LingoBuilder::make(['a' => 'b'])->isEmpty())->toBeFalse();
    });

    it('can check if not empty', function () {
        expect(LingoBuilder::make([])->isNotEmpty())->toBeFalse();
        expect(LingoBuilder::make(['a' => 'b'])->isNotEmpty())->toBeTrue();
    });

    it('can convert to array', function () {
        $builder = LingoBuilder::make(['Hello' => 'Halo']);

        expect($builder->toArray())->toBe(['Hello' => 'Halo']);
    });

    it('can convert to JSON', function () {
        $builder = LingoBuilder::make(['Hello' => 'Halo']);

        $json = $builder->toJson();

        expect($json)->toBeString();
        expect(json_decode($json, true))->toBe(['Hello' => 'Halo']);
    });

    it('can save to file', function () {
        $tempDir = sys_get_temp_dir().'/lingo-builder-'.getmypid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $filePath = $tempDir.'/translations.json';
        $builder = LingoBuilder::make(['Hello' => 'Halo', 'World' => 'Dunia']);

        $result = $builder->save($filePath);

        expect($result)->toBeTrue();
        expect(file_exists($filePath))->toBeTrue();

        $content = json_decode(file_get_contents($filePath), true);
        expect($content)->toHaveKey('Hello');
        expect($content)->toHaveKey('World');

        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('throws exception when saving without path and locale', function () {
        $builder = LingoBuilder::make(['Hello' => 'Halo']);

        expect(fn () => $builder->save())->toThrow(\InvalidArgumentException::class);
    });

    it('can chain multiple operations', function () {
        $builder = LingoBuilder::make([
            'z' => 'z',
            'a' => 'translated',
            'empty' => '',
        ]);

        $result = $builder
            ->addMissing(['new'])
            ->removeEmpty()
            ->sortKeys()
            ->get();

        $keys = array_keys($result);

        expect($result)->toHaveCount(3);
        expect($keys[0])->toBe('a');
        expect($result)->toHaveKey('new');
    });
});

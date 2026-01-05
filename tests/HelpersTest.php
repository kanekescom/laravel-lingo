<?php

use Kanekescom\Lingo\LingoBuilder;

it('has lingo helper function', function () {
    expect(function_exists('lingo'))->toBeTrue();
});

it('creates LingoBuilder instance from lingo helper', function () {
    $builder = lingo();

    expect($builder)->toBeInstanceOf(LingoBuilder::class);
});

it('can pass translations to lingo helper', function () {
    $builder = lingo(['Hello' => 'Halo']);

    expect($builder->get())->toBe(['Hello' => 'Halo']);
});

it('can chain methods from lingo helper', function () {
    $result = lingo(['z' => 'z', 'a' => 'a'])
        ->sortKeys()
        ->get();

    $keys = array_keys($result);

    expect($keys[0])->toBe('a');
    expect($keys[1])->toBe('z');
});

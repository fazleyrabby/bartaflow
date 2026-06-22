<?php

declare(strict_types=1);

use App\Support\PhoneNumber;

it('normalises Bangladeshi numbers to E.164', function (string $raw, string $expected) {
    expect(PhoneNumber::normalize($raw))->toBe($expected)
        ->and((string) PhoneNumber::fromInput($raw))->toBe($expected);
})->with([
    'local with leading zero' => ['01712345678', '+8801712345678'],
    'local with separators' => ['01712-345678', '+8801712345678'],
    'country code no plus' => ['8801712345678', '+8801712345678'],
    'full e164' => ['+8801712345678', '+8801712345678'],
    'spaces' => [' 0171 234 5678 ', '+8801712345678'],
]);

it('preserves explicit international numbers', function () {
    expect(PhoneNumber::normalize('+1 415 555 2671'))->toBe('+14155552671');
});

it('rejects empty or too-short input', function () {
    expect(PhoneNumber::normalize(''))->toBeNull()
        ->and(PhoneNumber::normalize('abc'))->toBeNull()
        ->and(PhoneNumber::isValid('123'))->toBeFalse();
});

it('returns null from tryFrom on invalid input', function () {
    expect(PhoneNumber::tryFrom('not-a-number'))->toBeNull();
});

it('throws from fromInput on invalid input', function () {
    PhoneNumber::fromInput('xx');
})->throws(InvalidArgumentException::class);

it('treats equal numbers from different formats as equal', function () {
    $a = PhoneNumber::fromInput('01712345678');
    $b = PhoneNumber::fromInput('+8801712345678');

    expect($a->equals($b))->toBeTrue();
});

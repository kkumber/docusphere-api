<?php

use App\Enums\CategoryType;
use Carbon\Carbon;

test("system generate 3 days due date for memorandum", function () {

    Carbon::setTestNow(Carbon::parse('2026-03-17'));

    $result = CategoryType::officialTimeline(CategoryType::MEMORANDUM->value);

    $this->assertInstanceOf(Carbon::class, $result);

    expect($result->toDateString())->toBe('2026-03-20');
});

test("adds 3 days of due date for unnumbered memorandum", function () {

    Carbon::setTestNow(Carbon::parse('2026-03-17'));

    $result = CategoryType::officialTimeline(CategoryType::UNNUMBERED_MEMORANDUM->value);

    $this->assertInstanceOf(Carbon::class, $result);

    expect($result->toDateString())->toBe('2026-03-20');
});

test("adds 3 days of due date for advisories", function () {

    Carbon::setTestNow(Carbon::parse('2026-03-17'));

    $result = CategoryType::officialTimeline(CategoryType::ADVISORY->value);

    $this->assertInstanceOf(Carbon::class, $result);

    expect($result->toDateString())->toBe('2026-03-20');
});

test("adds 7 days of due date for endorsements not including weekends", function () {

    Carbon::setTestNow(Carbon::parse('2026-03-20'));

    $result = CategoryType::officialTimeline(CategoryType::ENDORSEMENT->value);

    $this->assertInstanceOf(Carbon::class, $result);

    expect($result->toDateString())->toBe('2026-03-31');
});

test("adds 3 days of due date not including weekends", function () {

    Carbon::setTestNow(Carbon::parse('2026-03-20'));

    $result = CategoryType::officialTimeline(CategoryType::UNNUMBERED_MEMORANDUM->value);

    $this->assertInstanceOf(Carbon::class, $result);

    expect($result->toDateString())->toBe('2026-03-25');
});

?>
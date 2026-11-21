<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Business\Models\BusinessAlias;
use App\Business\Services\BusinessEmailMatcher;

class BusinessEmailMatcherTest extends BusinessTestCase
{
    public function test_matches_by_registration_code_in_subject(): void
    {
        Business::factory()->create([
            'name' => 'შпს ტესტი',
            'registration_code' => '123456789',
        ]);

        $matcher = new BusinessEmailMatcher;
        $result = $matcher->match('123456789 — პასუხი', '');

        $this->assertNotNull($result);
        $this->assertEquals('123456789', $result->registration_code);
        $this->assertEquals('registration_code', $matcher->matchedBy('123456789 — პასუხი', ''));
    }

    public function test_matches_by_business_name_in_subject(): void
    {
        Business::factory()->create([
            'name' => 'შпს ტესტი',
            'registration_code' => '123456789',
        ]);

        $matcher = new BusinessEmailMatcher;
        $result = $matcher->match('შпს ტესტი / საბუთები', '');

        $this->assertNotNull($result);
        $this->assertEquals('name', $matcher->matchedBy('შпს ტესტი / საბუთები', ''));
    }

    public function test_matches_by_alias_registration_code(): void
    {
        $business = Business::factory()->create([
            'name' => 'გიორგი გიორგაძე',
            'registration_code' => '01017039101',
        ]);

        BusinessAlias::create([
            'business_id' => $business->id,
            'name' => 'შпს მაგნოლია',
            'registration_code' => '426526725',
        ]);

        $matcher = new BusinessEmailMatcher;
        $result = $matcher->match('შпს მაგნოლია, ს/კ: 426526725', '');

        $this->assertNotNull($result);
        $this->assertEquals($business->id, $result->id);
        $this->assertEquals('alias_registration_code', $matcher->matchedBy('შпს მაგნოლია, ს/კ: 426526725', ''));
    }

    public function test_matches_by_alias_name_only(): void
    {
        $business = Business::factory()->create([
            'name' => 'გიორგი გიორგაძე',
            'registration_code' => '01017039101',
        ]);

        BusinessAlias::create([
            'business_id' => $business->id,
            'name' => 'შпს მაგნოლია',
            'registration_code' => null,
        ]);

        $matcher = new BusinessEmailMatcher;
        $result = $matcher->match('შпს მაგნოლია — პასუხი', '');

        $this->assertNotNull($result);
        $this->assertEquals($business->id, $result->id);
        $this->assertEquals('alias_name', $matcher->matchedBy('შпს მაგნოლია — პასუხი', ''));
    }

    public function test_returns_null_for_unmatched_subject(): void
    {
        Business::factory()->create([
            'name' => 'შпს ტესტი',
            'registration_code' => '123456789',
        ]);

        $matcher = new BusinessEmailMatcher;
        $result = $matcher->match('სრულიად უცხო თემა', '');

        $this->assertNull($result);
        $this->assertEquals('unmatched', $matcher->matchedBy('სრულიად უცხო თემა', ''));
    }

    public function test_direct_match_takes_priority_over_alias(): void
    {
        $owner = Business::factory()->create([
            'name' => 'გიორგი გიორგაძე',
            'registration_code' => '01017039101',
        ]);

        $other = Business::factory()->create([
            'name' => 'სხვა ბიზნესი',
            'registration_code' => '999999999',
        ]);

        BusinessAlias::create([
            'business_id' => $other->id,
            'name' => null,
            'registration_code' => '01017039101',
        ]);

        $matcher = new BusinessEmailMatcher;
        $result = $matcher->match('01017039101 — პასუხი', '');

        $this->assertEquals($owner->id, $result->id);
    }
}

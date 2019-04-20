<?php namespace Ghobaty\FormThrottler\Tests;

use Ghobaty\FormThrottler\FormThrottler;
use Illuminate\Validation\ValidationException;

class FormThrottlerTest extends TestCase
{
    /**
     * @test
     */
    public function it_throttles_requests()
    {
        $throttler = $this->makeThrottler();

        try {
            $throttler->throttle(function () {
                throw ValidationException::withMessages(['name' => 'Name is required.']);
            });
        } catch (ValidationException $e) {
            $this->assertSame(
                'Name is required.',
                $e->errors()["name"][0] ?? null
            );
        }

        try {
            $throttler->throttle(function () {
            });
        } catch (ValidationException $e) {
            $this->assertStringMatchesFormat(
                'Too many attempts. Please try again in %d seconds.',
                $e->errors()['throttle'][0] ?? null
            );
        }

    }

    /**
     * @test
     */
    public function it_clears_attempts_count_on_success()
    {
        $throttler = $this->makeThrottler(2);

        try {
            $throttler->throttle(function () {
                throw ValidationException::withMessages(['name' => 'Name is required.']);
            });
        } catch (ValidationException $e) {
        }


        $this->assertSame(1, $throttler->attempts());

        $throttler->throttle(function () {
            //
        });

        $this->assertSame(0, $throttler->attempts());
    }

    /**
     * @param int $maxAttempts
     * @return FormThrottler
     */
    protected function makeThrottler(int $maxAttempts = 1): FormThrottler
    {
        return $this->app->get(FormThrottler::class)->maxAttempts($maxAttempts);
    }
}

<?php

namespace Ghobaty\FormThrottler;

trait ThrottlesForms
{
    /**
     * @param callable $callback
     * @return mixed
     */
    protected function throttle(callable $callback)
    {
        return $this->throttler()->throttle($callback);
    }

    /**
     * @return \Ghobaty\FormThrottler\FormThrottler
     */
    protected function throttler()
    {
        /** @var \Ghobaty\FormThrottler\FormThrottler $ret */
        $ret = app(FormThrottler::class);

        $ret->decayMinutes($this->decayMinutes())
            ->maxAttempts($this->maxAttempts())
            ->validationField($this->throttleField());

        return $ret;
    }

    /**
     * Get the maximum number of attempts to allow.
     *
     * @return int
     */
    public function maxAttempts()
    {
        return property_exists($this, 'maxAttempts') ? $this->maxAttempts : 5;
    }

    /**
     * Get the number of minutes to throttle for.
     *
     * @return int
     */
    public function decayMinutes()
    {
        return property_exists($this, 'decayMinutes') ? $this->decayMinutes : 1;
    }

    /**
     * Get the name of the input field associated with the lock out response
     *
     * @return int
     */
    public function throttleField()
    {
        return property_exists($this, 'validationField') ? $this->validationField : 'throttle';
    }
}

<?php

/**
 * Wrapper around waMailAddressParser that makes it more tolerant to
 * misformatted input. Never throws exceptions.
 */
class mailerMailAddressParser extends waMailAddressParser
{
    protected function parseAddress()
    {
        try {
            parent::parseAddress();
        } catch (Exception $e) {
            $this->expected = null;
            $this->state = self::STATE_START;

            // To prevent infinite loop caused by the same error,
            // make sure the offset always increases.
            // Set the current position to be at the end of line.
            $next_eol = strpos($this->string, "\n", $this->offset);
            if ($next_eol === false) {
                $this->offset = strlen($this->string);
            } else {
                $this->offset = max($next_eol, $this->offset + 1);
            }
        }
    }
}


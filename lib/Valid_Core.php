<?php
class Valid_Core
{
    public function valid_required($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        return true;
    }

    public function valid_not_empty($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (Req::Request($arg['name']) == '') {
            return false;
        }
        return true;
    }

    public function valid_int($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (is_int(Req::Request($arg['name'])) === false) {
            return false;
        }
        return true;
    }

    public function valid_num($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (filter_var(Req::Request($arg['name']), FILTER_VALIDATE_INT) === false) {
            return false;
        }
        return true;
    }

    public function valid_string($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (is_string(Req::Request($arg['name'])) === false) {
            return false;
        }
        return true;
    }

    public function valid_alphanum($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match("/^[a-zA-Z0-9]+$/", Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    public function valid_char($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match("/^[a-zA-Z]+$/", Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    public function valid_double($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/^\d+(\.\d{2}+)?$/', Req::Request($arg['name'])) == 0) {
            return false;
        }
        return true;
    }

    // Unicode / multilingual alphabet validation
    public function valid_alpha_unicode($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/[^\p{L}\s]+/u', Req::Request($arg['name'])) === 1) {
            return false;
        }
        return true;
    }

    // Unicode / multilingual alphabet validation (backward-compatibility alias for legacy trchar rule)
    public function valid_trchar($arg)
    {
        return $this->valid_alpha_unicode($arg);
    }

    public function valid_date($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/[\d]{4}-[\d]{2}-[\d]{2}/', Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    // Landline phone format: 0 (455) 555-5555
    public function valid_phone_landline($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/^(0) \([1-9](\d){2}\) [1-9](\d){2}[-](\d){4}$/', Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    public function valid_web($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/^http(s)?:\/\/([a-zA-Z0-9]+\.)?([a-z]+\.)([a-zA-Z]){2,4}(\.[a-zA-Z]{2,4})?/', Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    // Mobile phone formatted: 0 (555) 555-5555
    public function valid_phone_mobile_formatted($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/^(0) \([5](\d){2}\) [1-9](\d){2}[-](\d){4}$/', Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    // Mobile phone spaced: 0 555 555 5555
    public function valid_phone_mobile_spaced($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/^(0) [5](\d){2} [1-9](\d){2} (\d){4}$/', Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    // Mobile phone raw: 05555555555
    public function valid_phone_mobile($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match('/^(05)([0-9]){2}[1-9]([0-9]){2}([0-9]){4}$/', Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    public function valid_email($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (preg_match("/.+@.+\..+./", Req::Request($arg['name'])) === 0) {
            return false;
        }
        return true;
    }

    public function valid_length($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        $count = $this->_count_chars(Req::Request($arg['name']));
        if ($count < $arg['arg']['min']) {
            return false;
        }
        if ($count > $arg['arg']['max']) {
            return false;
        }
        return true;
    }

    public function valid_national_id($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if ($this->_check_national_id(Req::Request($arg['name'])) === false) {
            return false;
        }
        return true;
    }

    public function valid_valid($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (is_callable($arg['arg']['function']) && $arg['arg']['function'](Req::Request($arg['name'])) === false) {
            return false;
        }
        return true;
    }

    public function valid_array($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (in_array(Req::Request($arg['name']), $arg['arg']) === false) {
            return false;
        }
        return true;
    }

    public function valid_equal($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (Req::Request($arg['name']) != Req::Request($arg['arg'])) {
            return false;
        }
        return true;
    }

    public function valid_not_equal($arg)
    {
        if (Req::is_Request($arg['name']) === false) {
            return false;
        }
        if (Req::Request($arg['name']) == Req::Request($arg['arg'])) {
            return false;
        }
        return true;
    }

    private function _check_national_id($id)
    {
        $idStr = (string)$id;
        if (strlen($idStr) !== 11 || !ctype_digit($idStr) || $idStr[0] === '0') {
            return false;
        }

        $digits = array_map('intval', str_split($idStr));
        $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];

        $tenthDigit = (($oddSum * 7) - $evenSum) % 10;
        if ($tenthDigit < 0) {
            $tenthDigit += 10;
        }

        if ($digits[9] !== $tenthDigit) {
            return false;
        }

        $firstTenSum = array_sum(array_slice($digits, 0, 10));
        $eleventhDigit = $firstTenSum % 10;

        return ($digits[10] === $eleventhDigit);
    }

    private function _count_chars($str)
    {
        return mb_strlen((string)$str, "utf-8");
    }
}
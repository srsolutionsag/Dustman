<?php

/**
 * Class ilMultiDateInputGUI
 * @author  Oskar Truffer <ot@studer-raimann.ch>
 */
class ilMultiDateInputGUI extends ilMultipleTextInput2GUI
{

    /**
     * @return bool returns true iff all values are in the form DD/MM.
     */
    public function checkInput()
    {
        $pl = new ilDustmanPlugin();
        foreach ($this->values as $value) {
            if (!$this->checkSingleInput($value)) {
                $this->setAlert($pl->txt('only_ddmm_allowed'));

                return false;
            }
        }

        return true;
    }

    /**
     * @param $value string
     * @return bool
     */
    protected function checkSingleInput($value)
    {
        $ddmm = explode("/", $value);
        try {
            // we try to initialize 2015 as it is a regular year...
            $date = new DateTime('2015-' . $ddmm[1] . '-' . $ddmm[0]);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}

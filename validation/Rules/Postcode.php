<?php

namespace Respect\Validation\Rules;

/**
 * Validate if given input is Royal Borough of Greenwich postcode
 */
class Postcode extends AbstractRule
{
    private $allowedDistricts = [
      'SE2', 'SE3', 'SE7', 'SE8', 'SE9',
      'SE10', 'SE12', 'SE13', 'SE18', 'SE28'
    ];

    public function validate($input)
    {
        $district = explode(' ', $input)[0];

        return (in_array(strtoupper($district), $this->allowedDistricts));
    }
}

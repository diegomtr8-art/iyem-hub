<?php

namespace Tests\Feature;

use App\Rules\CurpValida;
use App\Rules\RfcValido;
use App\Rules\TelefonoMexicano;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PersonaValidationTest extends TestCase
{
    public function test_rechaza_una_curp_con_menos_de_18_caracteres()
    {
        $validator = Validator::make(
            ['curp' => 'ABC123'],
            ['curp' => [new CurpValida]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_acepta_una_curp_con_formato_valido()
    {
        $validator = Validator::make(
            ['curp' => 'ABCD900101HDFRRN01'],
            ['curp' => [new CurpValida]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_rechaza_un_rfc_invalido()
    {
        $validator = Validator::make(
            ['rfc' => 'INVALIDO123'],
            ['rfc' => [new RfcValido]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_acepta_un_rfc_valido()
    {
        $validatorFisica = Validator::make(
            ['rfc' => 'ABCD900101ABC'],
            ['rfc' => [new RfcValido]]
        );
        $this->assertTrue($validatorFisica->passes());
    }

    public function test_normaliza_un_telefono_con_guiones_a_10_digitos()
    {
        $validatorValido = Validator::make(
            ['telefono' => '999-123-4567'],
            ['telefono' => [new TelefonoMexicano]]
        );
        $this->assertTrue($validatorValido->passes());
    }
}
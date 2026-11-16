<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Sms\Data\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendSmsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'phone'=>[
                'required',
                'string',
                function(string $attribute, mixed $value, Closure $fail):void{
                    if(!is_string($value)||PhoneNumber::tryFrom($value)===null)
                        {
                            $fail('Geçerli bir Türkiye cep telefonu numarası girin');
                        }
                }
            ],
            'message'=>['required','string','max:900'],
            'header'=>['nullable','string','max:11'],
        ];
    }

    public function phoneNumber():PhoneNumber{
        return PhoneNumber::from($this->string('phone')->toString());
    }
}

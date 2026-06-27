<?php

declare(strict_types=1);

namespace App\Http\Requests\Organizers;

use App\DataTransferObjects\Organizers\AddTeamMemberDto;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddTeamMemberRequest extends FormRequest
{
    /**
     * @return array<string, list<string|\Illuminate\Contracts\Validation\Rule|\Illuminate\Validation\Rules\In>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string', Rule::in(OrganizerRoles::values())],
        ];
    }

    public function toDto(): AddTeamMemberDto
    {
        $data = $this->validated();

        return new AddTeamMemberDto(
            userId: (int) $data['user_id'],
            role: (string) $data['role'],
        );
    }
}

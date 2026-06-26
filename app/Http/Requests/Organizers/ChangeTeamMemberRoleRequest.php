<?php

declare(strict_types=1);

namespace App\Http\Requests\Organizers;

use App\DataTransferObjects\Organizers\ChangeTeamMemberRoleDto;
use Illuminate\Foundation\Http\FormRequest;

final class ChangeTeamMemberRoleRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }

    public function toDto(): ChangeTeamMemberRoleDto
    {
        $data = $this->validated();

        return new ChangeTeamMemberRoleDto(
            userId: (int) $data['user_id'],
            roleId: (int) $data['role_id'],
        );
    }
}

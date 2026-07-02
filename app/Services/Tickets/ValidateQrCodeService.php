<?php

declare(strict_types=1);

namespace App\Services\Tickets;

use App\DataTransferObjects\Tickets\ValidationResult;
use App\Enums\AttendeeStatus;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;

final class ValidateQrCodeService
{
    /**
     * Valida un código de entrada para una lista de check-in específica.
     */
    public function validate(string $uniqueCode, int $checkInListId): ValidationResult
    {
        $checkInList = CheckInList::query()->find($checkInListId);

        if ($checkInList === null) {
            return $this->buildFailure('invalid_list', 'Check-in list not found.');
        }

        $attendee = Attendee::query()->where('unique_code', $uniqueCode)->first();

        if ($attendee === null) {
            return $this->buildFailure('invalid_code', 'Ticket code does not exist.');
        }

        $result = $this->checkTicketStatusAndEvent($attendee, $checkInList);

        if (!$result instanceof ValidationResult) {
            $result = $this->checkProductEligibility($attendee, $checkInList);
        }

        if (!$result instanceof ValidationResult) {
            $result = $this->checkDuplicateCheckIn($attendee, $checkInListId);
        }

        return $result ?? new ValidationResult(
            isValid: true,
            status: 'success',
            message: 'Ticket valid.',
            attendee: $attendee,
        );
    }

    private function checkTicketStatusAndEvent(Attendee $attendee, CheckInList $checkInList): ?ValidationResult
    {
        $result = null;

        if ($attendee->status === AttendeeStatus::Cancelled) {
            $result = $this->buildFailure('cancelled_ticket', 'This ticket has been cancelled.', $attendee);
        }

        $ticketOrder = null;

        if (!$result instanceof ValidationResult) {
            $ticketOrder = $attendee->ticketOrder;

            if ($ticketOrder === null) {
                $result = $this->buildFailure('invalid_order', 'Ticket order not found.', $attendee);
            }
        }

        if (!$result instanceof ValidationResult && $ticketOrder->event_id !== $checkInList->event_id) {
            $result = $this->buildFailure('wrong_event', 'This ticket belongs to another event.', $attendee);
        }

        return $result;
    }

    private function checkProductEligibility(Attendee $attendee, CheckInList $checkInList): ?ValidationResult
    {
        $result = null;

        $attendee->loadMissing('ticketOrderItem');
        $ticketOrderItem = $attendee->ticketOrderItem;

        if ($ticketOrderItem === null) {
            $result = $this->buildFailure('invalid_item', 'Ticket item not found.', $attendee);
        }

        if (!$result instanceof ValidationResult) {
            $productId = $ticketOrderItem->product_id;
            $hasRestrictions = $checkInList->eligibleProducts()->exists();

            if ($hasRestrictions) {
                $isEligible = $checkInList->eligibleProducts()->where('check_in_list_product.product_id', $productId)->exists();

                if (!$isEligible) {
                    $result = $this->buildFailure('not_eligible', 'This ticket type is not allowed at this access point.', $attendee);
                }
            }
        }

        return $result;
    }

    private function checkDuplicateCheckIn(Attendee $attendee, int $checkInListId): ?ValidationResult
    {
        /** @var ActiveCheckIn|null $existingCheckIn */
        $existingCheckIn = ActiveCheckIn::query()
            ->where('check_in_list_id', $checkInListId)
            ->where('attendee_id', $attendee->attendee_id)
            ->first();

        if ($existingCheckIn !== null) {
            return new ValidationResult(
                isValid: false,
                status: 'duplicate',
                message: 'This ticket has already been scanned at this access point.',
                attendee: $attendee,
                checkedInAt: $existingCheckIn->checked_in_at->toIso8601String(),
                activeCheckInId: $existingCheckIn->active_check_in_id,
            );
        }

        return null;
    }

    private function buildFailure(string $status, string $message, ?Attendee $attendee = null): ValidationResult
    {
        return new ValidationResult(
            isValid: false,
            status: $status,
            message: $message,
            attendee: $attendee,
        );
    }
}

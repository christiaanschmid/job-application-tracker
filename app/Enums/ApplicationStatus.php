<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Interested = 'interested';
    case Applied = 'applied';
    case PreliminaryInterview = 'preliminary_interview';
    case TechnicalInterview = 'technical_interview';
    case Offer = 'offer';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case NotSelected = 'not_selected';
    case Ghosted = 'ghosted';

    public function label(): string
    {
        return match ($this) {
            self::Interested => 'Interested',
            self::Applied => 'Applied',
            self::PreliminaryInterview => 'Preliminary Interview',
            self::TechnicalInterview => 'Technical Interview',
            self::Offer => 'Offer',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::NotSelected => 'Not Selected',
            self::Ghosted => 'Ghosted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Interested => 'gray',
            self::Applied => 'blue',
            self::PreliminaryInterview => 'yellow',
            self::TechnicalInterview => 'indigo',
            self::Offer => 'green',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::NotSelected => 'red',
            self::Ghosted => 'zinc',
        };
    }
}

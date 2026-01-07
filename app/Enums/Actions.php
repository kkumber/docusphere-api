<?php 

namespace App\Enums;

enum Actions: string 
{
    case ACKNOWLEDGED = 'Acknowledged';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
    case REVIEWED = 'Reviewed';
    case SIGNED = 'Signed';
    case RESPONDED = 'Responded';
    case COMPLETED = 'Completed';
}

?>
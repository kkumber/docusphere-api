<?php 
namespace App\Enums;


enum CategoryType: string
{
    case ADVISORY = 'advisory';
    case ENDORSEMENT = 'endorsement';
    case MEMORANDUM = 'memorandum';
    case UNNUMBERED_MEMORANDUM = 'unnumbered_memorandum';
}

?>
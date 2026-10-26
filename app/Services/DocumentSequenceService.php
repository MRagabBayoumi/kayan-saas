<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    /**
     * Get next sequential document number atomically with concurrency safety.
     * Guaranteed sequential numbering starting at 1, thread-safe via lockForUpdate().
     */
    public static function getNextNumber(string $documentType, string $prefix = 'PUR-', int $padding = 6): string
    {
        return DB::transaction(function () use ($documentType, $prefix, $padding) {
            $seq = DocumentSequence::where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $seq = DocumentSequence::create([
                    'document_type' => $documentType,
                    'prefix' => $prefix,
                    'next_number' => 1,
                    'padding' => $padding,
                ]);

                $seq = DocumentSequence::where('document_type', $documentType)
                    ->lockForUpdate()
                    ->first();
            }

            do {
                $currentNum = (int) $seq->next_number;
                $formattedNumber = $seq->prefix.str_pad((string) $currentNum, $seq->padding, '0', STR_PAD_LEFT);
                $seq->increment('next_number');
                $seq->refresh();

                $exists = false;
                if ($documentType === 'purchase_invoice') {
                    $exists = PurchaseInvoice::where('invoice_number', $formattedNumber)->exists();
                } elseif ($documentType === 'journal_entry') {
                    $exists = JournalEntry::where('entry_number', $formattedNumber)->exists();
                }
            } while ($exists);

            return $formattedNumber;
        });
    }
}

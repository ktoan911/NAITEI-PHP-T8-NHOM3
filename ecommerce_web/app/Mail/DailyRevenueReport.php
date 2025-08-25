<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class DailyRevenueReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $reportData;
    public $reportDate;

    /**
     * Create a new message instance.
     */
    public function __construct($reportData, $reportDate = null)
    {
        $this->reportData = $reportData;
        $this->reportDate = $reportDate ?? Carbon::yesterday()->format('d/m/Y');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Báo cáo doanh thu ngày ' . $this->reportDate,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-revenue-report',
            with: [
                'reportData' => $this->reportData,
                'reportDate' => $this->reportDate,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

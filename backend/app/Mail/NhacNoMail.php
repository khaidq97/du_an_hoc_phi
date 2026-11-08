<?php

namespace App\Mail;

use App\Models\HoaDon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// Email nhắc học viên đóng học phí. Nội dung nằm ở resources/views/emails/nhac-no.blade.php
class NhacNoMail extends Mailable
{
    public function __construct(public HoaDon $hoaDon)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nhắc đóng học phí - hóa đơn '.$this->hoaDon->ma_hoa_don);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.nhac-no');
    }
}

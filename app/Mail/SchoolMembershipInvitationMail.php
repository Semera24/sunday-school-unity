<?php

namespace App\Mail;

use App\Models\MembershipInvitation;
use App\Models\Organization;
use App\Models\SchoolMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolMembershipInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly MembershipInvitation $invitation,
        public readonly Organization $school,
        public readonly SchoolMembership $membership,
        public readonly string $acceptanceUrl,
    ) {}

    public function envelope(): Envelope
    {
        $schoolName = $this->school->name_am;

        return new Envelope(
            subject: "የሰንበት ት/ቤት የአባልነት እና የመለያ ትስስር ግብዣ — {$schoolName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $memberName = e($this->membership->person?->full_name ?? $this->invitation->invited_name ?? '');
        $schoolNameAm = e($this->school->name_am);
        $schoolNameEn = e($this->school->name_en ?? '');
        $expiresAt = e($this->invitation->expires_at?->toDateTimeString() ?? '');
        $url = e($this->acceptanceUrl);

        return <<<HTML
<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <title>የሰንበት ት/ቤት የአባልነት ግብዣ</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #1e293b; max-width: 640px; margin: 24px auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px;">
    <h2 style="margin-top: 0; color: #0f172a;">የሰንበት ት/ቤት የአባልነት እና የመለያ ትስስር ግብዣ</h2>
    <p>ሰላም <strong>{$memberName}</strong>፣</p>
    <p>በ<strong>{$schoolNameAm}</strong> ({$schoolNameEn}) የአባላት መዝገብዎን ከመግቢያ መለያ ጋር ለማስተሳሰር ግብዣ ተልኮልዎታል።</p>
    <p>እባክዎ ከታች ያለውን አገናኝ በመክፈት የይለፍ ቃልዎን ይምረጡ (ወይም ነባር መለያ ካለዎት በተጋበዘው ኢሜይል ገብተው ያረጋግጡ)። ግብዣውን ከተቀበሉ በኋላ የሰንበት ት/ቤቱ ዋና አስተባባሪ የመጨረሻ ማጽደቂያ ሲሰጡ የአባልነት መዳረሻዎ ይከፈታል።</p>
    <p style="margin: 24px 0;">
        <a href="{$url}" style="display: inline-block; background: #0f172a; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600;">ግብዣውን ክፈት / Accept Invitation</a>
    </p>
    <p style="font-size: 13px; color: #475569;">ቀጥታ አገናኝ (Direct URL): <a href="{$url}">{$url}</a></p>
    <p style="font-size: 12px; color: #64748b;">ይህ ግብዣ የሚያበቃበት ጊዜ (Expires at): {$expiresAt}</p>
</body>
</html>
HTML;
    }
}

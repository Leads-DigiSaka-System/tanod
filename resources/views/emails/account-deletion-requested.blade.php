<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deletion Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f7f6; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #163A39, #275E58); padding: 32px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 800;">TanodTractor</h1>
                            <p style="margin: 6px 0 0; color: rgba(255,255,255,0.7); font-size: 13px;">Account Deletion Request</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px 40px;">
                            <p style="margin: 0 0 16px; color: #1D2525; font-size: 15px; line-height: 1.6;">
                                Hi <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="margin: 0 0 16px; color: #62706E; font-size: 14px; line-height: 1.7;">
                                We received your request to permanently delete your TanodTractor account. Your account has been scheduled for deletion on:
                            </p>
                            <div style="background-color: #FFF3E0; border: 1px solid #FFE0B2; border-radius: 10px; padding: 16px 20px; text-align: center; margin: 20px 0;">
                                <p style="margin: 0; font-size: 18px; font-weight: 700; color: #E65100;">{{ $scheduledDate }}</p>
                                <p style="margin: 6px 0 0; font-size: 12px; color: #BF360C;">(7-day grace period)</p>
                            </div>
                            <p style="margin: 0 0 12px; color: #62706E; font-size: 14px; line-height: 1.7;">
                                <strong>What happens next:</strong>
                            </p>
                            <ul style="margin: 0 0 20px; padding-left: 20px; color: #62706E; font-size: 14px; line-height: 2;">
                                <li>Your account will remain accessible during the grace period.</li>
                                <li>After {{ $scheduledDate }}, all your data will be permanently deleted.</li>
                                <li>This includes your profile, bookings, tickets, feedback, and associated records.</li>
                            </ul>
                            <p style="margin: 0 0 20px; color: #62706E; font-size: 14px; line-height: 1.7;">
                                <strong>Changed your mind?</strong> You can cancel the deletion anytime before the scheduled date by logging into the app and going to <strong>Account &gt; Delete Account</strong>.
                            </p>
                            <p style="margin: 0; color: #9E9E9E; font-size: 12px; line-height: 1.6;">
                                If you did not request this deletion, please contact us immediately at <a href="mailto:support@tanodtractor.com" style="color: #275E58;">support@tanodtractor.com</a>.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f5f7f6; padding: 20px 40px; text-align: center; border-top: 1px solid #e8e8e8;">
                            <p style="margin: 0; color: #9E9E9E; font-size: 11px;">
                                &copy; {{ date('Y') }} TanodTractor &mdash; Leads Agricultural Products Corporation
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

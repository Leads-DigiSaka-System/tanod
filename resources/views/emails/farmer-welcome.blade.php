<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maligayang Pagdating sa TanodTractor</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); padding: 40px 40px 32px; text-align: center;">
                            <img src="{{ asset('images/logo.png') }}" alt="TanodTractor" width="80" style="margin-bottom: 16px;">
                            <h1 style="color: #ffffff; font-size: 24px; font-weight: 700; margin: 0; letter-spacing: -0.5px;">
                                Maligayang Pagdating!
                            </h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 8px 0 0;">
                                TanodTractor Farmer Account
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #1f2937; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Kumusta, <strong>{{ $farmerName }}</strong>!
                            </p>
                            <p style="color: #4b5563; font-size: 15px; line-height: 1.7; margin: 0 0 24px;">
                                Ikaw ay naidagdag na bilang farmer sa ilalim ni <strong>{{ $fcaName }}</strong> sa TanodTractor system. Maaari mo nang gamitin ang iyong account para ma-access ang mga serbisyo.
                            </p>

                            {{-- Credentials Card --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #15803d; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 16px;">
                                            Iyong Login Details
                                        </p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color: #6b7280; font-size: 13px; padding: 6px 0; width: 100px;">Phone:</td>
                                                <td style="color: #111827; font-size: 15px; font-weight: 600; padding: 6px 0;">{{ $farmerName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6b7280; font-size: 13px; padding: 6px 0;">Password:</td>
                                                <td style="color: #111827; font-size: 15px; font-weight: 600; padding: 6px 0;">
                                                    <code style="background-color: #dcfce7; padding: 4px 12px; border-radius: 6px; font-family: monospace; font-size: 16px; letter-spacing: 1px;">{{ $password }}</code>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Warning --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="color: #92400e; font-size: 14px; line-height: 1.6; margin: 0;">
                                            ⚠️ <strong>Paalala:</strong> Palitan ang iyong password pagkatapos ng unang login para sa seguridad ng iyong account.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #4b5563; font-size: 15px; line-height: 1.7; margin: 0;">
                                Kung mayroon kang katanungan, makipag-ugnayan sa iyong FCA o mag-email sa amin.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 40px; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0 0 4px;">
                                &copy; {{ date('Y') }} TanodTractor. Lahat ng karapatan ay nakalaan.
                            </p>
                            <p style="color: #d1d5db; font-size: 11px; margin: 0;">
                                Ang email na ito ay awtomatikong ipinadala. Huwag itong sagutin.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

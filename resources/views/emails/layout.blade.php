<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Notification' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        body, table, td, p, a, li { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f1f5f9; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        .wrapper { width: 100%; background-color: #f1f5f9; padding: 24px 0; }
        .content { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 28px 32px; text-align: center; }
        .header-title { color: #ffffff; font-size: 22px; font-weight: 700; margin: 0; letter-spacing: -0.02em; }
        .body-cell { padding: 32px 28px; color: #334155; font-size: 15px; line-height: 1.6; }
        .body-cell p { margin: 0 0 14px 0; }
        .body-cell p:last-child { margin-bottom: 0; }
        .footer { background-color: #f8fafc; padding: 20px 28px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer-text { color: #64748b; font-size: 12px; margin: 0; }
        .footer a { color: #475569; text-decoration: none; }
    </style>
</head>
<body>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="wrapper">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" class="content" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px;">
                    <tr>
                        <td class="header">
                            <h1 class="header-title">{{ $appName ?? 'R2BMarket' }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="body-cell">
                            {!! $bodyHtml ?? '' !!}
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <p class="footer-text">&copy; {{ date('Y') }} {{ $appName ?? 'R2BMarket' }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

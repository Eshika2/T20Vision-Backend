<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - T20Vision</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7fb; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);">
                    
                    <tr>
                        <td style="background-color: #0b5ed7; padding: 24px; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px; color: #ffffff;">T20Vision</h1>
                            <p style="margin: 8px 0 0; font-size: 14px; color: #dbeafe;">OTP Verification</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px 28px;">
                            <p style="margin: 0 0 16px; font-size: 16px;">Dear User,</p>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6;">
                                We received a request to verify your identity for your T20Vision account.
                                Please use the following One-Time Password (OTP) to continue:
                            </p>

                            <div style="margin: 24px 0; text-align: center;">
                                <span style="display: inline-block; background-color: #eff6ff; color: #0b5ed7; font-size: 30px; font-weight: bold; letter-spacing: 6px; padding: 16px 28px; border-radius: 10px; border: 1px solid #bfdbfe;">
                                    {{ $otp }}
                                </span>
                            </div>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6;">
                                Please enter this code in the application to complete the verification process.
                                For security reasons, do not share this code with anyone.
                            </p>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6;">
                                If you did not request this verification, you may safely ignore this email.
                            </p>

                            <p style="margin: 24px 0 0; font-size: 15px;">
                                Regards,<br>
                                <strong>The T20Vision Team</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 12px; color: #6b7280;">
                                This is an automated message from T20Vision. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
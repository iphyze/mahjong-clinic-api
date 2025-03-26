<?php
function emailVerificationTemplate($firstName, $emailCode, $expiresAt) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #fffcfd; }
            .mail-container { width: 28rem; background-color: white; padding: 50px 20px; text-align: center; margin: auto; box-shadow: 0 0px 10px rgba(0, 0, 0, 0.02); }
            .logo-img { width: 200px; margin: 20px auto; display: block; }
            .hi-user { font-size: 20px; color: #621d1f; text-align: center; }
            .hi-user-span { font-weight: 700; }
            .code-number { font-size: 28px; font-weight: 600; background-color:#fbf3f4; color: #ac1d21; padding: 10px; border-radius: 3px; border: 1px solid #ac1d21; margin: 20px auto; display: inline-block; }
            .footer { background-color: #621d1f; padding: 50px 20px; text-align: center; color: white; }
        </style>
    </head>
    <body>
        <img src='https://mahjon-db.goldenrootscollectionsltd.com/images/email-image.png' alt='Welcome' class='logo-img'>
        <div class='hi-user'>Congratulations <span class='hi-user-span'>$firstName</span>,</div>
        <div class='hi-user'>Your Mahjong Clinic App profile has been created</div>
        <div class='mail-container'>
            <img src='https://mahjon-db.goldenrootscollectionsltd.com/images/splash-logo.png' alt='Logo' class='logo-img'/>
            <p>Thank you for registering with us! To complete your account setup, please use the verification code below:</p>
            <div class='code-number'>$emailCode</div>
            <p>This code will expire on " . date("F j, Y, g:i a", strtotime($expiresAt)) . ".</p>
        </div>
        <div class='footer'>
            <p>This is an automated message, please do not reply directly to this email.</p>
            <p>© 2025 Mahjong Clinic Nigeria. All rights reserved.</p>
            <p>Developer | iphysdynamix</p>
        </div>
    </body>
    </html>";
}



function sendEmailVerificationTemplate($firstName, $emailCode, $expiresAt) {
    $expiresAtFormatted = date("F j, Y, g:i a", strtotime($expiresAt));
    return "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verify Your Email</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #fffcfd;
                max-width: 600px;
                margin: 0 auto;
                padding: 40px 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .mail-container {
                width: 28rem;
                background-color: white;
                box-shadow: 0 0px 10px rgba(0, 0, 0, 0.02);
                padding: 50px 20px;
            }
            .logo-img, .welcome-img {
                width: 200px;
                height: auto;
                margin: 20px auto;
            }
            .hi-user {
                font-size: 20px;
                color: #621d1f;
                font-weight: 700;
            }
            .code-number {
                width: 50%;
                background-color: #fbf3f4;
                color: #ac1d21;
                padding: 10px;
                font-size: 28px;
                font-weight: 600;
                border: 1px solid #ac1d21;
                border-radius: 3px;
                margin: 20px auto;
            }
            .footer {
                background-color: #621d1f;
                color: white;
                padding: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <img src='https://mahjon-db.goldenrootscollectionsltd.com/images/email-image.png' alt='img' class='welcome-img'>
        <div class='hi-user'>Hello $firstName,</div>
        <div>Your email verification code:</div>
        <div class='mail-container'>
            <img src='https://mahjon-db.goldenrootscollectionsltd.com/images/splash-logo.png' alt='logo' class='logo-img'>
            <p>Thank you for registering! Please use the verification code below:</p>
            <div class='code-number'>$emailCode</div>
            <p>This code will expire on $expiresAtFormatted. Please verify before it expires.</p>
        </div>
        <p>If you did not request this, please ignore this email.</p>
        <div class='footer'>
            <p>© 2025 Mahjong Clinic Nigeria. All rights reserved.</p>
            <p>Developer | iphysdynamix</p>
        </div>
    </body>
    </html>";

}


function emailVerifyTemplate($firstName) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
        <title>Email Verified</title>
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                background-color: #fffcfd;
                max-width: 600px;
                margin: 0 auto;
                padding: 40px 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .mail-container {
                width: 28rem;
                background-color: white;
                box-shadow: 0 0px 10px rgba(0, 0, 0, 0.02);
                padding: 50px 20px;
                text-align: center;
                margin: 0 auto;
            }
            .logo-img {
                width: 200px;
                margin: 20px auto;
                display: block;
            }
            .hi-user {
                font-size: 20px;
                color: #621d1f;
                text-align: center;
            }
            .hi-user-span {
                font-weight: 700;
            }
            .hi-user-subtext {
                font-size: 20px;
                color: #621d1f;
                text-align: center;
                margin-bottom: 20px;
            }
            .welcome-img {
                width: 180px;
                margin: 10px auto;
                display: block;
            }
            .code-text {
                font-size: 16px;
                color: rgb(86, 86, 86);
                text-align: center;
                margin-bottom: 20px;
            }
            .list-box {
                background-color: #faf0f0;
                padding: 20px 0;
                border-radius: 5px;
            }
            .list-flexbox {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                margin-bottom: 15px;
            }
            .list-icon {
                width: 50px;
                font-size: 14px;
                color: #ac1d21;
            }
            .list-text {
                font-size: 14px;
                color: #621d1f;
            }
            .footer {
                width: 28rem;
                text-align: center;
                background-color: #621d1f;
                padding: 50px 20px;
                margin: 0 auto;
                color: white;
            }
        </style>
    </head>
    <body>

        <img src="https://mahjon-db.goldenrootscollectionsltd.com/images/email-image.png" alt="img" class="welcome-img">
        
        <div class="hi-user">Hi <span class="hi-user-span">' . htmlspecialchars($firstName) . '</span>,</div>
        <div class="hi-user-subtext">Your email has been verified successfully!</div>
        
        <div class="mail-container">
            <img src="https://mahjon-db.goldenrootscollectionsltd.com/images/splash-logo.png" alt="logo-img" class="logo-img"/>
            
            <div class="code-text">
                You now have full access to all features and benefits of your account. Here\'s what you can do now:
            </div>
            <div class="list-box">
                <div class="list-flexbox">
                    <div class="list-icon fas fa-check-circle"></div>
                    <div class="list-text">Complete your profile information</div>
                </div>
                <div class="list-flexbox">
                    <div class="list-icon fas fa-check-circle"></div>
                    <div class="list-text">Explore all available features</div>
                </div>
                <div class="list-flexbox">
                    <div class="list-icon fas fa-check-circle"></div>
                    <div class="list-text">Connect with other users</div>
                </div>
                <div class="list-flexbox">
                    <div class="list-icon fas fa-check-circle"></div>
                    <div class="list-text">Customize your notification preferences</div>
                </div>
            </div>
        </div>

        <div class="mail-container">
            <div class="code-text">If you did not create an account with us, please ignore this email.</div>
            <div class="code-text">Thank you for choosing our service.</div>
            <div class="code-text">Best regards,</div>
            <div class="code-text">Mahjong Clinic App Team</div>
        </div>

        <div class="footer">
            <p>This is an automated message, please do not reply directly to this email.</p>
            <p>© 2025 Mahjong Clinic Nigeria. All rights reserved.</p>
            <p>Developer | iphysdynamix</p>
        </div>
    </body>
    </html>';
}


function passwordResetTemplate($firstName, $newPassword) {
return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-container {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .email-header {
            background-color: #4A90E2;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .email-body {
            padding: 30px;
            background-color: #ffffff;
        }
        .password-container {
            background-color: #f5f5f5;
            border: 1px dashed #cccccc;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            font-size: 18px;
        }
        .warning {
            color: #e74c3c;
            font-weight: bold;
        }
        .action-needed {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        h1 {
            margin-top: 0;
            color: #ffffff;
        }
        .logo {
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="logo">Your App Name</div>
            <h1>Password Reset Complete</h1>
        </div>
        <div class="email-body">
            <p>Hi ' . htmlspecialchars($firstName) . ',</p>
            <p>We have reset your password as requested. You can now log in using the temporary password below:</p>
            <div class="password-container">
                <strong>' . htmlspecialchars($newPassword) . '</strong>
            </div>
            <div class="action-needed">
                <p><strong>📢 Important Security Action Required:</strong></p>
                <p>For your security, please follow these steps:</p>
                <ol>
                    <li>Log in with the temporary password above</li>
                    <li>Go to <strong>Account Settings > Security</strong></li>
                    <li>Select <strong>"Change Password"</strong></li>
                    <li>Create a strong, unique password that you do not use elsewhere</li>
                </ol>
            </div>
            <p>This temporary password will expire in 24 hours for security reasons.</p>
            <p class="warning">⚠️ If you did not request this password reset, please contact our support team immediately as your account may be at risk.</p>
            <p>Thank you for using our service.</p>
            <p>Best regards,<br>The Your App Team</p>
        </div>
        <div class="footer">
            <p>This is an automated message, please do not reply directly to this email.</p>
            <p>© 2025 Your App Name. All rights reserved.</p>
            <p>123 App Street, Tech City, TC 12345</p>
        </div>
    </div>
</body>
</html>';
}



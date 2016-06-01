<html>
<head>
</head>
<body>

<br />
<div>
<p>Hi there,</p>
<br />
<p>It seems you’ve requested a password reset. Click on this link to create a new password or copy and paste this URL into your browser: </p>

<p><a href="{{ $data['token_url'] }}">{{ $data['token_url'] }}</a></p>
<br />
<br />
<p>If you didn’t request this, ignore this email; nothing will change.</p>
<br />
</body>
</html>

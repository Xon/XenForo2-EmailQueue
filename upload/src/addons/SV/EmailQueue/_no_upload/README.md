# Email Queuing Enhancements

Adjust email sending logic to retry more frequently, and not to aggressively abandon email sending.

This addon ensures all emails except a short list go via the email queue instead of blocking the request.

## Options

Adds the following options under "Email Options"
- Queue all email
- Email templates to exclude from queueing
- Failed email abandon threshold

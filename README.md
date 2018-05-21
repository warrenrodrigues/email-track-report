# Email Track Report
A script to easily generate a CSV report of email delivery, using [WHM API 1](https://documentation.cpanel.net/display/DD/Guide+to+WHM+API+1).

WHM API 1 doesn't have the best documentation, but is useful to automate certain tasks.
I needed a script to generate a CSV file of all the emails a certain user sent, along with error/bounce reports.

This script needed to handle a report of around 10,000 emails, but WHM's [emailtrack_search](https://documentation.cpanel.net/display/DD/WHM+API+1+Functions+-+emailtrack_search) API has an undocumented caveat of returning only 250 records, so I had to write some code to fetch the records in batches, using the [filter parameter](https://documentation.cpanel.net/display/DD/WHM+API+1+-+Filter+Output). The WHM API also returns the records in reverse order by default. I used the [API sort parameter](https://documentation.cpanel.net/display/DD/WHM+API+1+-+Sort+Output) to fix this as well.

*Note:* You will need to create an [API Token](https://documentation.cpanel.net/display/64Docs/Manage+API+Tokens) from your WHM panel, to use in this script.

## Licence
This script is intended as a starting point example of the WHM API 1. Feel free to fork/copy/use this script as per your requirement.

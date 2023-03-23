Early in 2020 Moneris shut down their US api's so we had to make some changes to the code. To summarize:

- see here for my question on a forum.... https://community.moneris.com/product-forums/f/5/t/1187
- esplus.moneris.com and espluseq.moneris.com (test url) are both no longer a thing
- has something to do with vantiv buying moneris or something.
- at the time of writing this the moneris documentation is out of date
- So, to get US payments working I just had to use the Canadian gateway with a second Moneris account. The 2nd account
has its own store ID, API token, and is configured to process USD.
- So, I just used the exact same endpoints as the CAD transactions for the USD store.
- The previous method when we hit US endpoints was to prepend "us_" to the transaction type. I had some
code in place to do this, because the library we used did not support this (had to do some ugly extending).
- Turns out, we no longer need to prepend "us_" to transaction types (In fact, we cannot).
- We just hit the Canadian gateway endpoints with the proper store ID and API token, and otherwise, there
are no differences between processing USD and CAD. So be clear, we have both CAD and USD store ID and APi token.

- The old US test credentials don't work on the CAD gateway endpoints. We have to use store5/yesguy (CA creds).
- This means that when we're in "US" and "Test mode", it's actually identical to "CA" and "Test mode", because
we're hitting the exact same endpoint with the same store ID and API token (store5/yesguy). Not sure this matters,
we don't really need to test USD because it works identical to CAD on our end at least.

- In case its not clear, the amount we send to the USD store is already a USD amount (may seems obvious...). 
- Currency conversion not necessary. 
- For example I billed by credit card $1 to the USD store and it showed up as $1.40 in my account.
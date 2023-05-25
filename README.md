## About Contact Crawler

Contact crawler is a small Laravel application built with spatie/crawler library. To use it, send a post request [url => 'https://www.example.com'] to the '/api/crawl' endpoint.

The robot crawls the first 10 URLs on the website, and extracts the email addresses and phone numbers found on them.

Please, note that you can increase the number of URLs crawled in the script.

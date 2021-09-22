Steps: 
- Run composer install to download dependencies 
- A test is added to validate the results based on provided example. See tests/Services/CommissionServiceTest.php 
- To run tests using `composer run test`
- Same with example, I added a script.php command, to simulate the given example on the exercise. (sample command: php script.php input.csv)
- The script.php command accepts a csv input file, with format strictly the same as provided sample: input.csv
- I added no further validation on the csv file as I wanted to focus on the function and structure of the app. 
- I also added an api to get the latest exchange rates, however, I disabled it for now to get accurate results based on example. See src/Service/CommissionService.php 
- By default, commission service uses the exchange rate EUR:USD - 1:1.1497, EUR:JPY - 1:129.53

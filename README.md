# Junior PHP developer recruitment task

Aim of this task is to order a package label using our API and provied data and process the response.

*Code should follow SOLID principles, but not over complicated, be readable*

## TO DO

Write a PHP CLI script which will do the following:

1. Reads data from two csv files. Can be found in `/source` folder 
2. Fires requests to Świat Przesyłek API to create couriers for provided lines. One request for each line. 
3. Retrieves labels (PNG) and package ids, saves labels it in `/labels` directory with random name and all package ids in one file ‘package_ids.txt’ files in the format: `PACKAGE_ID:LABEL_NAME`

## References:

Source files: 
- first file contains address data of sender & receiver
- second — weight & dimension of package

Each line of dimensions file should match the same line of address data file

API documentation is in `source` folder. Use this method: `courier/create-pre-routing` with the following credentials:

```
LOGIN: <provided_in_the_email>
API KEY: <provided_in_the_email>
Environment: test
```

## Checklist
- Labels should be saved in folder `/labels/<Today_date_in_DmY_format>/` and be rotated 90 degrees clockwise
- package_ids.txt should be in the root folder 

## Submitting

The result is accepted as a *pull request in the fork* of this repository.

*Good luck! If you have any questions feel free to ping us!*

This is test task "DB Data Generator"

Script is completed as CLI to use on Linux/UNIX environment. Tested with php5.3 / mysql5.1

*FILES*
- tbfill.php :: main code, can be started as CLI tool
- column.php :: classes for various column types, will add more classes here in future
- tbfill.json :: config file example
- tbfill.sql :: structure of database used to test
- description.txt :: original spec
- readme.txt :: this file

*DESCRIPTION*
- works as CLI script taking config file name from command-line;
- parameters stored in JSON config file;
- configurable via file: database credentials, script behaviour, number of strings to add to each database

*CONFIG FILE*
`tbfill.json` is used as config file name unless set as first command-line parameter like here: `tbfill.php alternate.json`. Config file description:
{
  "common":
  {
    "display_structure": "true", // display DB structure in human-readable format
    "display_queries": "true", // display queries to be executed on databas
    "fill": "true" // actually execute queries on database
  },
  "database":
  {
    "db_server": "localhost",
    "db_user": "username",
    "db_pass": "password",
    "db_name": "tbfill"
  },
  "tables": // numbr of strings to add to each table. if there's no parameter matching table name then script will disregard such table at all
  {
    "departments": 3,
    "dept_emp": 10,
    "dept_manager": 5,
    "employees": 7,
    "salaries": 2,
    "titles": 1
  }
}


*HOW TO IMPROVE*
- add support for Constraints
- add classes for more MYSQL field types
- make similar classes to use traits (i.e. fillers for all text types), but it will require more recent PHP than the used one

*USING AS CLI SCRIPT*
- check that first string of tbfill.php contains correst path to your PHP CLI interpreter as like this: `#!/usr/bin/php`;
- make tbfill.php executable;
- just run ./tbfill.php from command-line and enjoy!)


# mazed
A 3X3 maze game consisting of numbers in random order, and objective is to attain the ordered maze using minimum steps. Only vertical and horizontal adjacent tiles can be swapped.

1. Import the database using mazegame.sql
mysql -u <username> -p mazegame < mazegame.sql

2. Start the server:
php -S localhost:8888 api.php

3. To create a new game,
http://localhost:8888/game/<userid>/new

userid: Can be found in users table in database.

4. To submit a running game,
http://localhost:8888/game/<userid>/submit?moves=<no. of moves>

Developed by: Tejpal Yadav
Contact: tez.b12022@gmail.com
Suggestions are most welcomed :)

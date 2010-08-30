; <?php exit; ?>
db_connect = mysql://media:eVi0FIWJIZVLPwiB@localhost/mediaserver
debug_mode = 1
verbose = 1
secret = ynfB67rUWCKYIqKe
recursive_get = 1
database_enable = 1
admin_password = "Fuckyou123"
watched_0 = "/home/share/Music/"
watched_1 = "/home/share/Downloads/"
watched_2 = "/home/share/Pictures/"
watched_3 = "/home/share/Games/"
watched_4 = "/home/share/Software/"
watched_5 = "/home/share/Videos/"
watched_6 = "/home/share/Movies/"
watched_7 = "!/home/share/Downloads/incomplete/"
watched_8 = "!/home/share/Downloads/cache/"
watched_9 = "!/home/share/Music/Unsorted/"
local_users = "/var/www/dev/users/"

[alias_0]
paths = /home/share/
alias = /Shared/
paths_replace = "/^\/home\/(share\/)?/i"
alias_replace = "/^\/Shared\//i"

[alias_1]
paths = /var/www/dev/users/
alias = /Users/
paths_replace = "/^\/var\/(www\/(dev\/(users\/)?)?)?/i"
alias_replace = "/^\/Users\//i"
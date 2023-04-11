# Follows the end of a file and emits new lines as they are added.

Allows you to tail a file using HTTP.\
No system commands are executed. Uses native PHP functions only.

<hr/>

This PHP implementation is based on: `/usr/bin/tail -F -n 0 '/path/to/file.log'`

From `man tail`:

> The -f option causes tail to not stop when end of file is reached, but rather to wait for additional data to be
> appended to the input.

> The -F option implies the -f option, but tail will also check to see if the file being followed has been renamed or
> rotated. The file is closed and reopened when tail detects that the filename being read from has a new inode number.
\
> If the file being followed does not (yet) exist or if it is removed, tail will keep looking and will display the file
> from the beginning if and when it is created.

`-n 0` starts at the end of the file. `tail` defaults to a starting location of "-n 10" (cfr. the last 10 lines of the input).\
Hence this script will print the output of all new lines. No previous lines are emitted.

<hr/>

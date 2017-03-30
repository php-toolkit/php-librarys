# File system tool

## basic class

### FileSystem

class `inhere\library\files\FileSystem`

provide method:

```
isAbsPath
isAbsolutePath
pathFormat
exists
rename
isReadable
mkdir
chmod
chown
chmodDir
pathModeInfo
```

### Directory

class `inhere\library\files\Directory`, it extend of `FileSystem`.

provide method:

```
ls
getList
simpleInfo
getFiles
create
copy
delete
comparePath
yaSuo
jieYa
```

### File

class `inhere\library\files\File`, it extend of `FileSystem`.

provide method:

```
getName
getSuffix
getExtension
getInfo
getStat
save
write
openHandler
writeToFile
createAndWrite
getContents
move
delete
copy
combine
margePhp
```

### Read

class `inhere\library\files\Read`, it extend of `File`.

provide method:

```
ini
json
allLine
lines
symmetry
getLines5u3d
```

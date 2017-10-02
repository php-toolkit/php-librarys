# File system tool

## basic class

### FileSystem

class `Inhere\Library\Files\FileSystem`

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

class `Inhere\Library\Files\Directory`, it extend of `FileSystem`.

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

class `Inhere\Library\Files\File`, it extend of `FileSystem`.

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

class `Inhere\Library\Files\Read`, it extend of `File`.

provide method:

```
ini
json
allLine
lines
symmetry
getLines5u3d
```

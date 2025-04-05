#!/bin/sh

echo 
echo @####################################################################
echo This script is used to Encrypt the File
echo @####################################################################
echo 

REL_JAVA="$JAVA_HOME/bin/java"
JAVA="/usr/lib/jvm/java-11-openjdk-amd64/bin/java"

if [ -f $JAVA ]; then
    echo "Tool will use JVM at $JAVA"
else
    while [ ! -f $JAVA ]
    do
        echo Could not locate the JAVA home.
        echo Please enter JAVA home path : 
        read javaPath
        JAVA="$javaPath/bin/java"
    done
fi

# Use command line arguments instead of interactive input
base=$1
inputFileName=$2
Flag2=$3

base_path="$base/Encrypt.jar"

while [ ! -f "$base_path" ]
do
    echo Could not locate the Base Directory.
    echo Enter base working directory location: 
    read base
    base_path="$base/Encrypt.jar"
done

FCDB_BUILD="$base/Encrypt.jar:"
JAVA="/usr/lib/jvm/java-11-openjdk-amd64/bin/java"
JAVA_OPTS="-Xmx256m -XX:MetaspaceSize=32m -XX:MaxMetaspaceSize=64m"
$JAVA $JAVA_OPTS -Dfcat.basedir=$base -Dfcat.mode=D -Dfcat.jvm.id=1 -classpath $FCDB_BUILD com.iflex.fcat.tools.EncryptTool $inputFileName $Flag2 

echo 
echo Encryption Complete!!!


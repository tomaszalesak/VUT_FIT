#!/bin/sh
#
# IOS Projekt 1
# Tomas Zalesak, xzales13@stud.fit.vutbr.cz

iopt=
iarg=
nopt=
dopt=

while getopts :i:n o
do	case "$o" in
	i)	iopt=1
		iarg="$OPTARG";;
	n)	nopt=1;;
	*)	printf "Usage: dirgraph [-i FILE_ERE] [-n] [DIR]\n" >&2
		exit 1;;
	esac
done

# pracuju v $rootdir
shift $(($OPTIND - 1))
if [ $# -gt 1 ]; then
	exit 1
fi

if [ ! -z "$1" ]; then
    rootdir="$1"
    else rootdir=$(pwd)
fi

# s ignorovanim i normalizaci
if [ ! -z "$iopt" ] && [ ! -z "$nopt" ]; then

if [ -t 1 ] ; then sirkaterm=`tput cols`; else sirkaterm=79; fi

printf "Root directory: $rootdir\n"
printf "Directories: "
find "$rootdir" -type d | wc -l
printf "All files: "
find "$rootdir" -type f | wc -l

s1=$(find "$rootdir" -type f -size -100c | egrep -v "$iarg" | wc -l)
greatest=$s1
s2=$(find "$rootdir" -type f -size +100c -size -1024c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s2" ]; then
        greatest=$s2
fi
s3=$(find "$rootdir" -type f -size +1024c -size -10240c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s3" ]; then
        greatest=$s3
fi
s4=$(find "$rootdir" -type f -size +10240c -size -102400c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s4" ]; then
        greatest=$s4
fi
s5=$(find "$rootdir" -type f -size +102400c -size -1048576c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s5" ]; then
        greatest=$s5
fi
s6=$(find "$rootdir" -type f -size +1048576c -size -10485760c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s6" ]; then
        greatest=$s6
fi
s7=$(find "$rootdir" -type f -size +10485760c -size -104857600c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s7" ]; then
        greatest=$s7
fi
s8=$(find "$rootdir" -type f -size +104857600c -size -1073741824c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s8" ]; then
        greatest=$s8
fi
s9=$(find "$rootdir" -type f -size +1073741824c | egrep -v "$iarg" | wc -l)
if [ "$greatest" -le "$s9" ]; then
        greatest=$s9
fi

volnyprostor=$(($sirkaterm - 12))
if [ $volnyprostor -gt $greatest ]; then pomer=1
else pomer=$(($greatest / $volnyprostor))
fi

printf "File size histogram:\n"
printf "  <100 B  : "
p1=$(($s1 / $pomer))
while [ $p1 -gt 0 ]
do
   printf "#"
   p1=$(($p1 - 1))
done
printf "\n"

printf "  <1 KiB  : "
p2=$(($s2 / $pomer))
while [ $p2 -gt 0 ]
do
   printf "#"
   p2=$(($p2 - 1))
done
printf "\n"

printf "  <10 KiB : "
p3=$(($s3 / $pomer))
while [ $p3 -gt 0 ]
do
   printf "#"
   p3=$(($p3 - 1))
done
printf "\n"

printf "  <100 KiB: "
p4=$(($s4 / $pomer))
while [ $p4 -gt 0 ]
do
   printf "#"
   p4=$(($p4 - 1))
done
printf "\n"

printf "  <1 MiB  : "
p5=$(($s5 / $pomer))
while [ $p5 -gt 0 ]
do
   printf "#"
   p5=$(($p5 - 1))
done
printf "\n"

printf "  <10 MiB : "
p6=$(($s6 / $pomer))
while [ $p6 -gt 0 ]
do
   printf "#"
   p6=$(($p6 - 1))
done
printf "\n"

printf "  <100 MiB: "
p7=$(($s7 / $pomer))
while [ $p7 -gt 0 ]
do
   printf "#"
   p7=$(($p7 - 1))
done
printf "\n"

printf "  <1 GiB  : "
p8=$(($s8 / $pomer))
while [ $p8 -gt 0 ]
do
   printf "#"
   p8=$(($p8 - 1))
done
printf "\n"

printf "  >=1 GiB : "
p9=$(($s9 / $pomer))
while [ $p9 -gt 0 ]
do
   printf "#"
   p9=$(($p9 - 1))
done
printf "\n"


printf "File type histogram:\n"

g2=$(file -b `find . -type f | egrep -v "$iarg"` | sort | uniq -c | sort -n -r | head -1| awk '{printf $1}')
vp2=$(( $sirkaterm - 47 ))
if [ $vp2 -gt $g2 ]; then p2=1
else p2=$(($g2 / $vp2))
fi

file -b `find "$rootdir" -type f | egrep -v "$iarg"` | sort | uniq -c | sort -n -r | head | awk -v pomer="$p2" '{i=$1; $1=""; if(length($0)>43){printf " %-41.41s%s",$0,"..."; printf ": ";} else{printf " %-44.44s",$0; printf ": ";} for (j=i/pomer;j>0;j--) printf "#";printf "\n"}'

    exit 0
fi

# s ignorovanim
if [ ! -z "$iopt" ]; then
    
printf "Root directory: $rootdir\n"
printf "Directories: "
find "$rootdir" -type d | egrep -v "$iarg" | wc -l
printf "All files: "
find "$rootdir" -type f | egrep -v "$iarg" | wc -l

printf "File size histogram:\n"
printf "  <100 B  : "
size=0
size=$(find "$rootdir" -type f -size -100c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <1 KiB  : "
size=0
size=$(find "$rootdir" -type f -size +100c -size -1024c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <10 KiB : "
size=0
size=$(find "$rootdir" -type f -size +1024c -size -10240c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <100 KiB: "
size=0
size=$(find "$rootdir" -type f -size +10240c -size -102400c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <1 MiB  : "
size=0
size=$(find "$rootdir" -type f -size +102400c -size -1048576c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <10 MiB : "
size=0
size=$(find "$rootdir" -type f -size +1048576c -size -10485760c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <100 MiB: "
size=0
size=$(find "$rootdir" -type f -size +10485760c -size -104857600c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <1 GiB  : "
size=0
size=$(find "$rootdir" -type f -size +104857600c -size -1073741824c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  >=1 GiB : "
size=0
size=$(find "$rootdir" -type f -size +1073741824c | egrep -v "$iarg" | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"


printf "File type histogram:\n"
# find "$rootdir" -exec file {} \; | cut -d: -f2 | uniq -c | sort -r


file -b `find "$rootdir" -type f | egrep -v "$iarg"` | sort | uniq -c | sort -n -r | head | awk '{i=$1;$1=""; if(length($0)>43){printf " %-41.41s%s",$0,"..."; printf ": ";} else{printf " %-44.44s",$0; printf ": ";} for (;i>0;i--) printf "#";printf "\n"}'
    
exit 0
fi

# s normalizaci
if [ ! -z "$nopt" ]; then
    if [ -t 1 ] ; then sirkaterm=`tput cols`; else sirkaterm=79; fi

printf "Root directory: $rootdir\n"
printf "Directories: "
find "$rootdir" -type d | wc -l
printf "All files: "
find "$rootdir" -type f | wc -l

s1=$(find "$rootdir" -type f -size -100c | wc -l)
greatest=$s1
s2=$(find "$rootdir" -type f -size +100c -size -1024c | wc -l)
if [ "$greatest" -le "$s2" ]; then
        greatest=$s2
fi
s3=$(find "$rootdir" -type f -size +1024c -size -10240c | wc -l)
if [ "$greatest" -le "$s3" ]; then
        greatest=$s3
fi
s4=$(find "$rootdir" -type f -size +10240c -size -102400c | wc -l)
if [ "$greatest" -le "$s4" ]; then
        greatest=$s4
fi
s5=$(find "$rootdir" -type f -size +102400c -size -1048576c | wc -l)
if [ "$greatest" -le "$s5" ]; then
        greatest=$s5
fi
s6=$(find "$rootdir" -type f -size +1048576c -size -10485760c | wc -l)
if [ "$greatest" -le "$s6" ]; then
        greatest=$s6
fi
s7=$(find "$rootdir" -type f -size +10485760c -size -104857600c | wc -l)
if [ "$greatest" -le "$s7" ]; then
        greatest=$s7
fi
s8=$(find "$rootdir" -type f -size +104857600c -size -1073741824c | wc -l)
if [ "$greatest" -le "$s8" ]; then
        greatest=$s8
fi
s9=$(find "$rootdir" -type f -size +1073741824c | wc -l)
if [ "$greatest" -le "$s9" ]; then
        greatest=$s9
fi

volnyprostor=$(($sirkaterm - 12))
if [ $volnyprostor -gt $greatest ]; then pomer=1
else pomer=$(($greatest / $volnyprostor))
fi


printf "File size histogram:\n"
printf "  <100 B  : "
p1=$(($s1 / $pomer))
while [ $p1 -gt 0 ]
do
   printf "#"
   p1=$(($p1 - 1))
done
printf "\n"

printf "  <1 KiB  : "
p2=$(($s2 / $pomer))
while [ $p2 -gt 0 ]
do
   printf "#"
   p2=$(($p2 - 1))
done
printf "\n"

printf "  <10 KiB : "
p3=$(($s3 / $pomer))
while [ $p3 -gt 0 ]
do
   printf "#"
   p3=$(($p3 - 1))
done
printf "\n"

printf "  <100 KiB: "
p4=$(($s4 / $pomer))
while [ $p4 -gt 0 ]
do
   printf "#"
   p4=$(($p4 - 1))
done
printf "\n"

printf "  <1 MiB  : "
p5=$(($s5 / $pomer))
while [ $p5 -gt 0 ]
do
   printf "#"
   p5=$(($p5 - 1))
done
printf "\n"

printf "  <10 MiB : "
p6=$(($s6 / $pomer))
while [ $p6 -gt 0 ]
do
   printf "#"
   p6=$(($p6 - 1))
done
printf "\n"

printf "  <100 MiB: "
p7=$(($s7 / $pomer))
while [ $p7 -gt 0 ]
do
   printf "#"
   p7=$(($p7 - 1))
done
printf "\n"

printf "  <1 GiB  : "
p8=$(($s8 / $pomer))
while [ $p8 -gt 0 ]
do
   printf "#"
   p8=$(($p8 - 1))
done
printf "\n"

printf "  >=1 GiB : "
p9=$(($s9 / $pomer))
while [ $p9 -gt 0 ]
do
   printf "#"
   p9=$(($p9 - 1))
done
printf "\n"


printf "File type histogram:\n"

g2=$(file -b `find . -type f` | sort | uniq -c | sort -n -r | head -1| awk '{printf $1}')
vp2=$(( $sirkaterm - 47 ))
if [ $vp2 -gt $g2 ]; then p2=1
else p2=$(($g2 / $vp2))
fi

file -b `find "$rootdir" -type f` | sort | uniq -c | sort -n -r | head | awk -v pomer="$p2" '{i=$1; $1=""; if(length($0)>43){printf " %-41.41s%s",$0,"..."; printf ": ";} else{printf " %-44.44s",$0; printf ": ";} for (j=i/pomer;j>0;j--) printf "#";printf "\n"}'

    exit 0
fi

# bez ignorovani i normalizace
printf "Root directory: $rootdir\n"
printf "Directories: "
find "$rootdir" -type d | wc -l
printf "All files: "
find "$rootdir" -type f | wc -l

printf "File size histogram:\n"
printf "  <100 B  : "
size=0
size=$(find "$rootdir" -type f -size -100c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <1 KiB  : "
size=0
size=$(find "$rootdir" -type f -size +100c -size -1024c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <10 KiB : "
size=0
size=$(find "$rootdir" -type f -size +1024c -size -10240c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <100 KiB: "
size=0
size=$(find "$rootdir" -type f -size +10240c -size -102400c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <1 MiB  : "
size=0
size=$(find "$rootdir" -type f -size +102400c -size -1048576c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <10 MiB : "
size=0
size=$(find "$rootdir" -type f -size +1048576c -size -10485760c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <100 MiB: "
size=0
size=$(find "$rootdir" -type f -size +10485760c -size -104857600c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  <1 GiB  : "
size=0
size=$(find "$rootdir" -type f -size +104857600c -size -1073741824c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"

printf "  >=1 GiB : "
size=0
size=$(find "$rootdir" -type f -size +1073741824c | wc -l)
while [ $size -gt 0 ]
do
   printf "#"
   size=`expr $size - 1`
done
printf "\n"


printf "File type histogram:\n"
# find "$rootdir" -exec file {} \; | cut -d: -f2 | uniq -c | sort -r


file -b `find "$rootdir" -type f` | sort | uniq -c | sort -n -r | head | awk '{i=$1;$1=""; if(length($0)>43){printf " %-41.41s%s",$0,"..."; printf ": ";} else{printf " %-44.44s",$0; printf ": ";} for (;i>0;i--) printf "#";printf "\n"}'


exit 0

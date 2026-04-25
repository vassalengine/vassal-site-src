#!/bin/bash

api=https://api.github.com
repo=vassalengine/vassal
release=
token=
steps=
force=0
need=0

# --- Help message ---------------------------------------------------
usage() {
    cat <<-EOF
	Usage: $0 [--force] --release RELEASE --token TOKEN STEPS

	where RELEASE is the release number, and TOKEN is either a
	GitHub personal access token, or a file containinig such a
	token.

	If --force is given, the forcen re-download and unpack.

	STEPS says what to do, and can be one or more of

	- Files:  Download the release files
	- JavaDoc: Download the Java API documentation
	- ReferenceManual: Download the reference manual
	- MavenReleases: Download and unpack maven releases
	- Notes: Download release notes
	- FlatPak: Download the FlatPak recipe
	
	If no STEPS are specified, then it defaults to Files, JavaDoc,
	ReferenceManual, and MavenReleases.
	EOF
}

# --- Process command line -------------------------------------------
while test $# -gt 0 ; do
    arg=$(echo $1 | tr '[A-Z]' '[a-z]')
    case x$arg in
        x-h|x--help)
            usage
            exit 0
            ;;
        x-r|x--release)
            release=$2
            shift
            ;;
        x-t|x--token)
            token=$2
            shift
            if test -f $token ; then
                token=`cat $token`
            fi
            ;;
        x-f|x--force)
            force=1
            ;;
        x-u|x--repo)
            repo=$2
            shift
            ;;
        xfiles)
            steps="${steps} $arg"
            ;;
        xjavadoc|xreferencemanual|xmavenreleases|xnotes|xflatpak)
            steps="${steps} $arg"
            need=1
            ;;
        x*)
            echo "$0: Unknown argument: $1"
            exit 1
            ;;
    esac
    shift
done

# --- Default steps --------------------------------------------------
if [ "x$steps" = "x" ] ; then
    steps="files javadoc referencemanual mavenreleases"
fi

# --- Check the arguments --------------------------------------------
if [ "x$release" = "x" ] ; then
    echo "No release specified"
    exit 1
fi
if [ $need -gt 0 ] && [ "x$token" = "x" ] ; then
    echo "No personal access token specified"
    exit 1
fi

# --- Get an artifact ------------------------------------------------
get_artefact() {
    release="$1" ; shift
    token="$1" ; shift
    name="$1" ; shift

    echo -n "Getting artefact: ${name} ..."
    action_id=$(curl -s -L ${api}/repos/${repo}/actions/workflows/release.yml/runs | jq  ".workflow_runs[] | select(.head_branch==\"${release}\") | .id" | head -n 1)
    if [ "x$action_id" = "x" ] ; then
        echo "Failed to get GitHub action id for ${release}"
        exit 1
    fi

    url=$(curl -s -L ${api}/repos/${repo}/actions/runs/${action_id}/artifacts | jq -r ".artifacts[] | select(.name==\"${name}\") | .archive_download_url")
    curl -s -L -H "Authorization: token ${token}" "${url}" -o ${name}.zip

    if [ ! -f ${name}.zip ] ; then
        echo "Failed to get artefact ${name} from GitHub action {action_id}"
        exit 1
    fi
    
    echo " done"
}

# --- Unpack an artefact to sub-dir, and link latest -----------------
unpack_artefact() {
    release=$1 ; shift 
    target=$1 ; shift
    zipfile=$1 ; shift

    echo -n "Unpacking artefact ${zipfile} to ${target}/${release} ..."
    if [ "x$target" = "x" ] ; then
        echo "No target directory"
        exit 1
    fi
    
    if [ ! -f ${zipfile} ] ; then
        echo "No ${zipfile} to unpack"
        exit 1
    fi

    if [ $force -gt 0 ] ; then
        rm -rf ${target}/${release}
    fi

    if [ -d ${target}/${release} ] ; then
        echo "already present and not forcing"
        rm -f ${zipfile}
        return 0
    fi
    

    mkdir -p ${target}/${release}
    unzip -qq ${zipfile} -d ${target}/${release}
    rm -f ${zipfile}
    echo -n " latest link ..."
    (cd ${target} && rm -f latest && ln -s ${release} latest)

    echo " done"
}

# --- Get release files ----------------------------------------------
get_files() {
    release=$1 ; shift

    mkdir -p releases

    pushd releases

    assets=$(curl -s ${api}/repos/${repo}/releases/tags/${release} | jq -r .assets[].browser_download_url)
    for asset in ${assets}  ; do
        file=$(basename "$asset")
        echo -n "Getting ${file} ..."
        if [ $force -gt 0 ] ; then
            rm -f ${file}
        fi

        if [ -f ${file} ] ; then
            echo " already present and not forcing"
            continue
        fi
        
        curl -s -L "$asset" -o "$file"
        echo " done"
    done

    popd
}


# --- Do the steps ---------------------------------------------------
for step in $steps ; do
    case $step in
        files)
            get_files "$release"
            ;;
        javadoc)
            get_artefact "$release" "$token" "JavaDoc"
            unpack_artefact "$release" "javadoc" "JavaDoc.zip"
            ;;
        referencemanual)
            get_artefact "$release" "$token" "ReferenceManual"
            unpack_artefact "$release" "doc" "ReferenceManual.zip"
            ;;
        mavenreleases)
            get_artefact "$release" "$token" "MavenReleases"
            mkdir -p maven
            unzip -qq -o MavenReleases.zip -d maven/ -x '*-metadata.*'
            mkdir -p maven-tmp
	    unzip -qq -o MavenReleases.zip -d maven-tmp/ '*-metadata.xml'
            for i in $(find maven-tmp -name "*-metadata.xml") ; do
                d=$(dirname $i | sed 's,[^/]*/,,')
                f=$(basename $i)

                if [ ! -f maven/${d}/${f} ] ; then
                    continue
                fi
                cp maven/${d}/${f} maven/${d}/${f}~
                
                b=$(echo ${d} | tr '/' '_')

                rm -f ${b}*

                csplit -q -f ${b} $i '/<versions>/+1'
                cat ${b}00 > ${b}.xml
                grep "<version>" maven/${d}/${f} >> ${b}.xml
                cat ${b}01 >> ${b}.xml

                mv ${b}.xml maven/${d}/${f}
                
                for alg in md5 sha1 ; do 
                    cat maven/${d}/${f} | \
                        ${alg}sum | \
                        sed 's/ *-//' > \
                            maven/${d}/${f}.${alg}
                done
                
                rm -f ${b}*
            done
            rm -rf maven-tmp
            rm -f MavenReleases.zip
            ;;
        notes)
            get_artefact "$release" "$token" "NOTES"
            ;;
        flatpak)
            get_artefact "$release" "$token" "flatpak-recipe"
            ;;
        *)
            echo "Unknown step: $step - ignoring"
            ;;
    esac
done
#
# EOF
#

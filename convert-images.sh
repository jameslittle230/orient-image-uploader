#!/bin/bash
#convert-images.sh

export HOME=/home/bitnami
DIRS_TO_PROCESS=()
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/"

rm ${DIR}tmp/*.zip
for D in `ls -d ${DIR}tmp/*/`
do
	if [ -f ${D}/.uploaded ]; then
		curl -X POST --data-urlencode 'payload={"text":"Deleted files to clean up"}' https://hooks.slack.com/services/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
		echo "${D} is being deleted now: completed"
		rm -rf ${D}
	elif [ -f ${D}/.incomplete ]; then
		curl -X POST --data-urlencode 'payload={"text":"Deleted files to clean up"}' https://hooks.slack.com/services/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
		echo "${D} is being deleted now: failed"
		rm -rf ${D}
	elif [ -f ${D}/.processing ]; then
		curl -X POST --data-urlencode 'payload={"text":"Deleted files to clean up"}' https://hooks.slack.com/services/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
		break
	elif [ -f ${D}/.done ]; then
		curl -X POST --data-urlencode 'payload={"text":"Adding directory to queue"}' https://hooks.slack.com/services/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
		echo "${D} will be processed"
		DIRS_TO_PROCESS+=(${D})
		touch ${D}/.processing
	else
		echo "${D} will be deleted on the next run"
		touch ${D}/.incomplete
	fi
done

for P in ${DIRS_TO_PROCESS}
do
	#echo ${P}
	for IMG_D in `ls -d ${P}*/`
	do
		echo -e ${IMG_D}
		IMG=`find ${IMG_D} -iname '*.jpg' -o -iname '*.jpeg'`
		IMGNAME=`ls ${IMG_D} | grep '.jp*'`
		IMGNAME=`basename ${IMGNAME} .jpg`
		IMGNAME=`basename ${IMGNAME} .jpeg`
		echo ${IMG}: ${IMGNAME}

		cp ${IMG} ${IMG_D}/${IMGNAME}"_2.jpg"
		cp ${IMG} ${IMG_D}/${IMGNAME}"_3.jpg"
		cp ${IMG} ${IMG_D}/${IMGNAME}"_4.jpg"

		convert ${IMG_D}/${IMGNAME}"_2.jpg" -gamma 2.4 -colorspace CMYK -quality 90 ${IMG_D}/${IMGNAME}"_cmyk.jpg"
		rm ${IMG_D}/${IMGNAME}"_2.jpg"
		echo "Created CMYK"

		convert ${IMG_D}/${IMGNAME}"_3.jpg" -colorspace gray -quality 90 -gamma 2.5 ${IMG_D}/${IMGNAME}"_gray.jpg"
		rm ${IMG_D}/${IMGNAME}"_3.jpg"
		echo "Created gray"

		convert ${IMG_D}/${IMGNAME}"_4.jpg" -quality 65 ${IMG_D}/${IMGNAME}"_web.jpg"
		rm ${IMG_D}/${IMGNAME}"_4.jpg"
		echo "Created web"

		curl -X POST --data-urlencode 'payload={"text":"Finished converting '${IMGNAME}'"}' https://hooks.slack.com/services/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
	done

	zip -r ${P%/}.zip ${P}
	echo "Created zip"
	curl -X POST --data-urlencode 'payload={"text":"Created zip"}' https://hooks.slack.com/services/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

	aws s3 cp ${P%/}.zip s3://bowdoinorient-uploader
	touch ${P}/.uploaded

	ZIPNAME=`basename ${P%/}`".zip"
	echo ${ZIPNAME}
	curl -X POST --data-urlencode 'payload={"text":"Uploaded! https://s3.amazonaws.com/bowdoinorient-uploader/'${ZIPNAME}'"}' https://hooks.slack.com/services/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
done

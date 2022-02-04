SS1_WEBP:=images/screenshots/screenshot1-1224.webp images/screenshots/screenshot1-1044.webp images/screenshots/screenshot1-864.webp images/screenshots/screenshot1-624.webp images/screenshots/screenshot1-444.webp

SS2_WEBP:=images/screenshots/screenshot2-1224.webp images/screenshots/screenshot2-1044.webp images/screenshots/screenshot2-864.webp images/screenshots/screenshot2-624.webp images/screenshots/screenshot2-444.webp

SS3_WEBP:=images/screenshots/screenshot3-1224.webp images/screenshots/screenshot3-1044.webp images/screenshots/screenshot3-864.webp images/screenshots/screenshot3-624.webp images/screenshots/screenshot3-444.webp

SS4_WEBP:=images/screenshots/screenshot4-1224.webp images/screenshots/screenshot4-1044.webp images/screenshots/screenshot4-864.webp images/screenshots/screenshot4-624.webp images/screenshots/screenshot4-444.webp

SS5_WEBP:=images/screenshots/screenshot5-1224.webp images/screenshots/screenshot5-1044.webp images/screenshots/screenshot5-864.webp images/screenshots/screenshot5-624.webp images/screenshots/screenshot5-444.webp

SS6_WEBP:=images/screenshots/screenshot6-1224.webp images/screenshots/screenshot6-1044.webp images/screenshots/screenshot6-864.webp images/screenshots/screenshot6-624.webp images/screenshots/screenshot6-444.webp

#SS1:=$(SS1_WEBP) $(SS1_WEBP:.webp=.jpg) $(SS1_WEBP:.webp=.avif)
#SS2:=$(SS2_WEBP) $(SS2_WEBP:.webp=.jpg) $(SS2_WEBP:.webp=.avif)
#SS3:=$(SS3_WEBP) $(SS3_WEBP:.webp=.jpg) $(SS3_WEBP:.webp=.avif)
#SS4:=$(SS4_WEBP) $(SS4_WEBP:.webp=.jpg) $(SS4_WEBP:.webp=.avif)
#SS5:=$(SS5_WEBP) $(SS5_WEBP:.webp=.jpg) $(SS5_WEBP:.webp=.avif)
#SS6:=$(SS6_WEBP) $(SS6_WEBP:.webp=.jpg) $(SS6_WEBP:.webp=.avif)

SS1:=$(SS1_WEBP) $(SS1_WEBP:.webp=.jpg)
SS2:=$(SS2_WEBP) $(SS2_WEBP:.webp=.jpg)
SS3:=$(SS3_WEBP) $(SS3_WEBP:.webp=.jpg)
SS4:=$(SS4_WEBP) $(SS4_WEBP:.webp=.jpg)
SS5:=$(SS5_WEBP) $(SS5_WEBP:.webp=.jpg)
SS6:=$(SS6_WEBP) $(SS6_WEBP:.webp=.jpg)

ALL:=index.html about.html download.html licenses.html contact.html status.php $(SS1) $(SS2) $(SS3) $(SS4) $(SS5) $(SS6)

VCUR:=3.6.4
YEAR:=$(shell date +'%Y')

JINJA_ARGS:=-Dyear=${YEAR} -Dcurrent_version=${VCUR}

all: $(ALL)

clean:
	$(RM) $(ALL) 

.PHONY: all clean

%.html: templates/%.html templates/base.html
	jinja2 ${JINJA_ARGS} $< -o $@

%.php: templates/%.php templates/base.html
	jinja2 ${JINJA_ARGS} $< -o $@

images/screenshots/screenshot1-%.png images/screenshots/screenshot1-%.jpg images/screenshots/screenshot1-%.webp: images-src/screenshot1.png

images/screenshots/screenshot2-%.png images/screenshots/screenshot2-%.jpg images/screenshots/screenshot2-%.webp: images-src/screenshot2.png

images/screenshots/screenshot3-%.png images/screenshots/screenshot3-%.jpg images/screenshots/screenshot3-%.webp: images-src/screenshot3.png

images/screenshots/screenshot4-%.png images/screenshots/screenshot4-%.jpg images/screenshots/screenshot4-%.webp: images-src/screenshot4.png

images/screenshots/screenshot5-%.png images/screenshots/screenshot5-%.jpg images/screenshots/screenshot5-%.webp: images-src/screenshot5.png

images/screenshots/screenshot6-%.png images/screenshots/screenshot6-%.jpg images/screenshots/screenshot6-%.webp: images-src/screenshot6.png

PRE_OPTS:=-resize
POST_OPTS:=-unsharp 0x0.75+0.75+0.008 -strip

images/screenshots/screenshot1-%.jpg: images-src/screenshot1.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot2-%.jpg: images-src/screenshot2.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot3-%.jpg: images-src/screenshot3.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot4-%.jpg: images-src/screenshot4.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot5-%.jpg: images-src/screenshot5.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot6-%.jpg: images-src/screenshot6.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot1-%.webp: images-src/screenshot1.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot2-%.webp: images-src/screenshot2.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot3-%.webp: images-src/screenshot3.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot4-%.webp: images-src/screenshot4.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot5-%.webp: images-src/screenshot5.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

images/screenshots/screenshot6-%.webp: images-src/screenshot6.png
	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@

#images/screenshots/screenshot1-%.png: images-src/screenshot1.png
#	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@
#
#images/screenshots/screenshot2-%.png: images-src/screenshot2.png
#	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@
#
#images/screenshots/screenshot3-%.png: images-src/screenshot3.png
#	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@
#
#images/screenshots/screenshot4-%.png: images-src/screenshot4.png
#	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@
#
#images/screenshots/screenshot5-%.png: images-src/screenshot5.png
#	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@
#
#images/screenshots/screenshot6-%.png: images-src/screenshot6.png
#	convert $< $(PRE_OPTS) $*x $(POST_OPTS) $@
#
#images/screenshots/screenshot1-%.avif: images/screenshots/screenshot1-%.png
#	avifenc -o $@ -s 0 $<
#
#images/screenshots/screenshot2-%.avif: images/screenshots/screenshot2-%.png
#	avifenc -o $@ -s 0 $<
#
#images/screenshots/screenshot3-%.avif: images/screenshots/screenshot3-%.png
#	avifenc -o $@ -s 0 $<
#
#images/screenshots/screenshot4-%.avif: images/screenshots/screenshot4-%.png
#	avifenc -o $@ -s 0 $<
#
#images/screenshots/screenshot5-%.avif: images/screenshots/screenshot5-%.png
#	avifenc -o $@ -s 0 $<
#
#images/screenshots/screenshot6-%.avif: images/screenshots/screenshot6-%.png
#	avifenc -o $@ -s 0 $<

favicon.ico: images/favicons/vassal-32x32.png images/favicons/vassal-16x16.png
	convert $* $@



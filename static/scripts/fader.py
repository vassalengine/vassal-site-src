import sys

#
# n images
# a = duration for cross fading
# b = presentation time for one image
# t = total animaton duration = (a+b)*n
#
# animation-delay = i*(a+b)
#
# Percentages for keyframes:
#  0%
#  a/t*100%
#  (a+b)/t*100% = 1/n*100%
#  (2*a+b)/t*100%
#  100%
#

n = int(sys.argv[1])
a = int(sys.argv[2])
b = int(sys.argv[3])

t = (a+b)*n

print(f"""
.screenshot {{
  position: absolute;
  left: 0;
  animation-name: fade;
  animation-duration: {t}s;
  animation-iteration-count: infinite;
  opacity: 0;
}}

@keyframes fade {{
  0% {{
    opacity: 0;
  }}
  {int(100*a/t)}% {{
    opacity: 1;
  }}
  {int(100*(a+b)/t)}% {{
    opacity: 1;
  }}
  {int(100*((2*a+b)/t))}% {{
    opacity: 0;
  }}
  100% {{
    opacity: 0;
  }}
}}""")

for i in range(1, n + 1):
    print(f"""
.screenshot:nth-of-type({i}) {{
  animation-delay: {(i-1)*(a+b)}s;
}}""")

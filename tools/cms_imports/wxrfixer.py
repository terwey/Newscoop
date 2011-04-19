#!/usr/bin/env python
#
# Tries to fix invalid WXR files - on embedded CDATA
# Run it as: wxrfixer.py input.file output.file
#

import sys, os, re

class WXR_Fixer(object):

    # the wp problems tend to be around js/css cdata parts, see
    # http://en.wikipedia.org/wiki/CDATA
    js_cdata = re.compile(r"]]>[\s]*</script>", re.UNICODE)
    css_cdata = re.compile(r"]]>[\s]*\*/[\s]*</style>", re.UNICODE)

    js_subst = "]]]]><![CDATA[>\n</script>"
    css_subst = "]]]]><![CDATA[>*/\n</style>"

    # printing the error messages
    def write_err(self, msg):
        if not msg.endswith("\n"):
            msg += "\n"
        try:
            sys.stderr.write(msg)
        except:
            pass

    # the fixing function itself
    def try_fix_wp(self, infile_name, outfile_name):
        infile = None
        outfile = None

        try:
            infile = open(infile_name, "r")
        except Exception as exc:
            self.write_err("can not open '" + infile_name + "' for read: " + str(exc))
            return False
        try:
            outfile = open(outfile_name, "w")
        except Exception as exc:
            self.write_err("can not open '" + outfile_name + "' for write: " + str(exc))
            infile.close()
            return False

        last_line = None
        try:
            # testing on line pairs
            last_line = None
            for line in infile.readlines():
                if last_line:
                    double_line = last_line + line
                    checked_dl = re.sub(self.js_cdata, self.js_subst, double_line)
                    if double_line != checked_dl:
                        outfile.write(checked_dl)
                        last_line = None
                        continue
                    checked_dl = re.sub(self.css_cdata, self.css_subst, double_line)
                    if double_line != checked_dl:
                        outfile.write(checked_dl)
                        last_line = None
                        continue

                    outfile.write(last_line)

                last_line = line

            # if a last line remained
            if last_line:
                to_use_last = True
                if to_use_last:
                    checked_line = re.sub(self.js_cdata, self.js_subst, last_line)
                    if checked_line != line:
                        outfile.write(checked_line)
                        to_use_last = False
                if to_use_last:
                    checked_line = re.sub(self.css_cdata, self.css_subst, last_line)
                    if checked_line != line:
                        outfile.write(checked_line)
                        to_use_last = False
                if to_use_last:
                    outfile.write(line)

        except Exception as exc:
            self.write_err("an error during xml checking: " + str(exc))
            pass

        outfile.close()
        infile.close()

        return True

if __name__ == '__main__':
    # calling the fix attempt
    fixer = WXR_Fixer()

    if 3 > len(sys.argv):
        fixer.write_err("shall be run as " + sys.argv[0] + " input.file output.file")
        sys.exit(1)

    infile_name = sys.argv[1]
    outfile_name = sys.argv[2]

    fixer.try_fix_wp(infile_name, outfile_name)



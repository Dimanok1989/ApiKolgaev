import sys, json, zipfile

uid=sys.argv[1]
tempdir=sys.argv[2]+'/'

with open(tempdir+uid+'.json') as f:
    file_content = f.read()
    data = json.loads(file_content)

zipname=tempdir+uid+'.zip'

newzip=zipfile.ZipFile(zipname,'w')

for i in range(len(data)):
    print(data[i])
    newzip.write(data[i]['path'], data[i]['file'])

newzip.close()
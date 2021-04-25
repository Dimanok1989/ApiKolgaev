import zipfile #подключаем модуль

zipname=r'../../storage/app/drive/temp/bdseoru.zip'

try:
    newzip=zipfile.ZipFile(zipname,'w') #создаем архив
    print("Архив bdseoru.zip на диске С:/ создан.")
    newzip.write(zipname, '123/createaip2.py') #добавляем файл 
    newzip.close() #закрываем архив
except:
    print("Что-то пошло не так...")
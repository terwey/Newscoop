#Newscoop Notices
## Family announcements, notices, classified ads

### Requirements
- Admin Backend
 - manage notices and categories
 
- Frontend
 - display notices and make them searchable


### currently available endpoints/controllers
Admin_NoticeController
 - list 
 - edit (currently creating new ones)
 - list Categories
 - edit Categories
 
Admin_NoticeRestController 
 - list (queries with tags)
 - get  (by id)
 - post 
 [..]
 
Default_NoticeController (placeholder for now)


### Services 
 - registered
 - NoticeService (encapsulates calls to repo)

## 1. Mapped Entities/Data Models
introduced two new extensions/dependencies
for more documentation please check
https://github.com/l3pp4rd/DoctrineExtensions and 
https://github.com/FabienPennequin/DoctrineExtensions-Taggable

NoticeCategory (Tree and sluggable)
 - currently not related to Notices
 - used as a blueprint for available tags (later on maybe tag types)

Notice
 - has many tags
 - can be queried with tags
     - /admin/notice-rest
     - /admin/notice-rest/?query=Geburtsanzeigen/Basel

Tagging (cross table between Notice and Tag)
Tag

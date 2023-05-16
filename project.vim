set path=.,buildlib,doc,scripts,sql/**/*,src/**/*,static_src/**/*,template/**/*,test/**/*
nnoremap <SPACE> :find<SPACE>

" build
set makeprg=node\ make.js
nnoremap <Leader>b :!node make.js<CR>

" test
nnoremap <Leader>t :!test/uitest.py<CR>

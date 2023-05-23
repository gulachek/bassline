set path=,,buildlib,doc,scripts,sql/**,src/**,static_src/**,template/**
set path+=test,test/hello/**,test/phpunit/**,test/uilib
nnoremap <SPACE> :find<SPACE>

" build
set makeprg=node\ make.js
nnoremap <Leader>b :!node make.js<CR>

" test
nnoremap <Leader>t :!test/uitest.py<CR>

" reset
nnoremap <Leader>r :!scripts/reset.sh<CR>

set path=,,buildlib,doc,bin,scripts,sql/**,src/**,static_src/**,template/**
set path+=test,test/hello/**,test/phpunit/**,test/uilib
nnoremap <SPACE> :find<SPACE>

" build
set makeprg=node\ make.mjs
nnoremap <Leader>b :!node make.mjs --outdir assets<CR>

" test
nnoremap <Leader>t :!scripts/test.sh<CR>

" reset
nnoremap <Leader>r :!scripts/reset.sh<CR>

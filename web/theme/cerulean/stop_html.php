                        </div><!-- /.span10 -->
                        <div class="span2">
                            <?php //echo get_user(array('prenom', 'nom'), TRUE, STR_EN_LIGNE); ?>
                        </div><!-- /.span2 -->
                    </div><!-- /.row-fluid -->
                    
                    
                    <footer class="footer">
                        <div class="row-fluid">
                            <div class="span10">
                                <p>
                                <?php
                                    $flux='';
                                    $flux.=CFG_TITRE.' V'.CFG_VERSION.' ('.ucwords(str_replace('_', ' ', CFG_CLASS)).'::'.ucwords(CFG_TYPE).')'.' - '.CFG_DATE;
                                    if(!empty($session_user_id))
                                        $flux.= ' - '.$cfg_profil['nom'].' '.$cfg_profil['prenom']."\n";
                
                                    $foo=@$session->count();
                
                                    $flux.= ' - '.$foo.' ';
                                    if($foo > 1)
                                        $flux.=sprintf(STR_CONNECT, 's', 's');
                                    else
                                        $flux.=sprintf(STR_CONNECT, ' ', ' ');
                
                                    echo $flux;
                                ?>               
                                </p>                        
                            </div><!-- /.span10 -->
                            <div class="span2">
                                <p class="right">
                                <?php
                                    $flux='';
                                    if(defined("CFG_DOC") && CFG_DOC==TRUE) {                    
                                        $url=CFG_PATH_HTTP.'/divers/doc/';
                                        $flux.='&nbsp;<a href="'.$url.'" target="_blank">'.STR_DOC.'</a>';
                                    }
                                    
                                    echo $flux;    
                                ?>        
                                </p>
                            </div><!-- /.span2 -->
                        </div><!-- /.row-fluid -->
                    </footer>
                            
                </div><!-- /.span12 -->
            </div><!-- /.row-fluid -->
        </div><!-- /.container-fluid -->            
    </body>
    
    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
        
</html>
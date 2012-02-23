<?php
/**
 * Takes a url and converts the rendered HTML to a graphic saved to a nominated path
 *
 * Uses the wkhtmltoimage unix utility. See the documentation for available options
 * http://madalgo.au.dk/~jakobt/wkhtmltoxdoc/wkhtmltoimage_0.10.0_rc2-doc.html
 *
 * Depends on the sfImageTransformPlugin  
 *
 * @package default
 * @author Dan Steele <dan@dansteele.net>
 */
 
class dsHtml2Image
{
  
  protected $default = array(
              'format' => 'jpg',
              'quality' => '80'
            );
    
  public function __construct($url, $save_path, array $options = array())
  {
    $this->checkSystem();
    $this->url = $url;
    $this->save_path = $save_path;
    $this->setOptions($options);
  }
  
  /**
   * Checks for dependant packages and throws an sfException if not found
   *
   * @return void
   * @author Dan Steele
   */
  protected function checkSystem()
  {
    if(!shell_exec("dpkg-query -W xvfb"))
    {
      throw new sfException('This plugin requires the xvfb package');
    }
  }
  
  /**
   * Gets the path of the wkhtmltoxdoc utility
   *
   * @return void
   * @author Dan Steele
   */
  protected function getUtility()
  {
    return sfConfig::get('app_dsHtml2ImagePlugin_utility', false);
  }
  
  /**
   * Merges the default options with any user specific ones
   *
   * @param array $options 
   * @return void
   * @author Dan Steele
   */
  public function setOptions(array $options = array())
  {
    $allowed = sfConfig::get('app_dsHtml2ImagePlugin_options');
    $this->options = $this->default;
    foreach($options as $option => $value)
    {
      if(in_array($option,$allowed))
      {
        $this->options[$option] = $value;
      }
      else
      {
        throw new sfException('The option '.$option.' is not supported');
      }
    }
  }
  
  /**
   * Constructs an options string from the default and options passed
   *
   * @return string
   * @author Dan Steele
   */
  protected function getOptionsString()
  {
    $options_string = '';
    foreach($this->options as $option => $value)
    {
      $options_string .= ' --'.$option;
      if(!empty($value))
      {
        $options_string .= ' '.$value;
      }
    }
    return $options_string;
  }
  
  /**
   * Returns the desired log file lcoation
   *
   * @return string
   * @author Dan Steele
   */
  protected function getLogFile()
  {
    return sfConfig::get('app_dsHtml2ImagePlugin_log');
  }  
  
  /**
   * Constructs the shell command to make the snapshot
   *
   * @return void
   * @author Dan Steele
   */
  protected function getCommand()
  {
    return 'xvfb-run -a --error-file='.$this->getLogFile().' '.$this->getUtility().' '.$this->getOptionsString().' '.$this->url.' '.$this->save_path;
  }
  
  /**
   * Unsets the file path as wkhtmltoxdoc seems to have beef with that
   *
   * @return void
   * @author Dan Steele
   */
  protected function execute()
  {
    $now = time();
    if(file_exists($this->save_path))
    {
      unlink($this->save_path);
    }
    
    if(shell_exec($this->getCommand()) == NULL)
    {
      throw new sfException('Failed to run: '.$this->getCommand());
    }
  }
  
  /**
   * Returns an sfImage instance of the screen capture
   *
   * @return sfImage object
   * @author Dan Steele
   */
  public function getImage()
  {
    $this->execute();
    $img = new sfImage($this->save_path,'image/jpg');
    return $img;
  }
  
}
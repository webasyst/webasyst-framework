<?php

class blogSlugValidator extends waStringValidator
{
    const SUBJECT_BLOG = 'blog';
    const SUBJECT_POST = 'post';

    const ERROR_REQUIRED = 'required';
    const ERROR_URL_IN_USE = 'url_in_use';
    const ERROR_INVALID = 'invalid';

    private $subject = self::SUBJECT_BLOG;

    protected $options = array(
        self::ERROR_REQUIRED => true,
        self::ERROR_URL_IN_USE => true,
        self::ERROR_INVALID => true,
        'id' => null		// use in isInUse method
    );

    protected $errors_map = array(
        self::ERROR_REQUIRED => false,
        self::ERROR_URL_IN_USE => false,
        self::ERROR_INVALID => false
    );

    protected function init()
    {
        parent::init();

        $this->setMessage(self::ERROR_REQUIRED, _w('%subject% URL must not be empty'));
        $this->setMessage(self::ERROR_URL_IN_USE, _w('%subject% URL is in use. Please enter another URL'));
        $this->setMessage(self::ERROR_INVALID, _w('%subject% URL is invalid'));
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function isValid($value)
    {
        $this->clearErrors();

        $variables = array('subject' => ucfirst($this->subject));

        if ($this->getOption(self::ERROR_REQUIRED, false) && $this->isEmpty($value)) {
            $this->setError($this->getMessage(self::ERROR_REQUIRED, $variables));
            $this->errors_map[self::ERROR_REQUIRED] = true;
        }

        if ($this->getOption(self::ERROR_URL_IN_USE, false) && $this->isInUse($value)) {
            $this->setError($this->getMessage(self::ERROR_URL_IN_USE, $variables));
            $this->errors_map[self::ERROR_URL_IN_USE] = true;
        }
        if (!preg_match('/^[a-zA-Z0-9_-]*$/', $value)) {
            $this->setError($this->getMessage(self::ERROR_INVALID, $variables));
            $this->errors_map[self::ERROR_INVALID] = true;
        }

        return !$this->getErrors();
    }

    public function isInUse($value)
    {
        if ($this->subject == self::SUBJECT_BLOG) {
            $model = new blogBlogModel();
        } else {
            $model = new blogPostModel();
        }

        $cond = $this->options['id'] ? 'url = :url AND id != i:id' : 'url = :url';

        return $model
            ->select('id')
            ->where($cond, array('url' => $value, 'id' => $this->options['id']))
            ->limit(1)
            ->fetch();
    }

    public function isError($error_code)
    {
        return $this->errors_map[$error_code];
    }

}
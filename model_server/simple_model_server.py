# -*- coding: utf-8 -*-
"""
Created on Fri Aug  9 17:19:49 2019

@author: damian
"""

import tensorflow as tf
import numpy as np
import sys

import flask

import datetime
from functools import wraps

from time import sleep, time

try:
  import jwt
except ModuleNotFoundError:
  pass


__VER__ = '0.9.2.4'

class NonReentrantModelServer:
  def __init__(self, model, model_fun, session, graph, log, fun_input, fun_output):
    self.is_tf1 = False
    if self.is_tf1:
      log.P("NonReentrantModelServer using TF1 ({})".format(log.TF_VER))
    else:
      log.P("NonReentrantModelServer using TF2")
    self.graph = graph
    self.model = model
    self.model_fun = model_fun
    if not hasattr(self.model, self.model_fun):
      raise ValueError("The class {} does not have function '{}'".format(
          self.model.__class__.__name__, model_fun))
    self.session = session
    self.in_use = False
    self.fun_input_convert = fun_input
    self.fun_output_convert = fun_output
    self.version = __VER__
    self.log = log
    self.n_servings = 0
    
    
  def predict(self, inputs, client_id=None, wait_time=0):
    STR_MAX = 1024
    results = None
    if self.in_use:
      return
    self.in_use = True
    _ver = None
    if hasattr(self.model,'version'):
      _ver = self.model.version
    self.log.P("")
    self.log.P("Serving prediction to client '{}' using tf {} - ipython: {}".format(
      client_id, tf.__version__, self.log.is_running_from_ipython))
    self.log.P("  Model:   {} ({}) ver:{}".format(hex(id(self.model)), self.model.__class__.__name__, _ver))
    self.log.P("  Graph:   {}".format(self.graph)) #hex(id(self.graph))))
    self.log.P("  Session: {}".format(self.session)) #hex(id(self.session))))
    _error_vals = [None,None,None]
    try:
      x_inputs = self.fun_input_convert(inputs)
      ########
      if inputs is not None:
        l1 = len(inputs)
        l2 = len(x_inputs)
      else:
        l1 = None
        l2 = None
        
      __info = "input {}:{} and outputs {}:{}".format(
          type(inputs), l1, type(x_inputs), l2)
      self.log.iP("Executed fun_input_convert w info: " +__info )
      ########
      
    except:
      _err_type = "{}".format(sys.exc_info()[0])
      _error_vals[0] = "{}".format(sys.exc_info()[1])
      self.log.P("ERROR {} RAISED IN fun_input_convert:\n\n{}\n\n".format(
          _err_type, _error_vals[0]))
      y_results = None
      
    if x_inputs is None or _error_vals[0]:
      # some clean-up
      pass
    else:
      if self.is_tf1:
        tf.keras.backend.set_session(self.session)
      
      predict_func = getattr(self.model, self.model_fun)
      try:
        if self.is_tf1:
          with self.graph.as_default():
            y_results = predict_func(x_inputs)
        else:
          y_results = predict_func(x_inputs)
        ########
        self.log.iP("Executed predict_func w Inputs {} and Outputs {}".format(len(y_results), len(x_inputs)))
        ########
      except:
        _err_type = "{}".format(sys.exc_info()[0])
        _error_vals[1] = "{}".format(sys.exc_info()[1])
        self.log.P("ERROR {} RAISED IN PREDICTION ENGINE:\n\n{}\n\n".format(
            _err_type, _error_vals[1]))
        y_results = None
                
      try:
        results = self.fun_output_convert(y_results)
        ########
        self.log.iP("Executed fun_output_convert w Inputs {} and Outputs {}".format(len(y_results), len(results)))
        ########
      except:
        _err_type = "{}".format(sys.exc_info()[0])
        _error_vals[2] = "{}".format(sys.exc_info()[1])
        self.log.P("ERROR {} RAISED IN fun_output_convert:\n\n{}\n\n".format(
            _err_type, _error_vals[2]))
        y_results = None
        
    if results is None:
      results = {}
    results['client'] = client_id  # add the client id to the json response
    if np.any(np.array(_error_vals) != None):
      for i,_err in enumerate(_error_vals):
        results['ERROR{}_DEBUG'.format(i)] = _err
      results['INFO_DEBUG'] = "Please send this data to dev team. Thank you."
    if _ver is not None:
      _key = "{}".format(self.model.__class__.__name__)
      results[_key] = _ver
    results['log'] = self.log.version
    if hasattr(self.log, 'timeseries_benchmarker'):
      results['ts'] = self.log.timeseries_benchmarker.version
    str_res = str(results)
    str_res = str_res if len(str_res) <=STR_MAX else str_res[:STR_MAX]+'...'
    self.n_servings += 1
    self.log.P("Response #{} for client:'{}': {}".format(self.n_servings, client_id, str_res))
    self.log.P(" ")
    c_time = time()
    while (c_time + wait_time) > time():
      sleep(1)
    self.in_use = False
    return results

###############################################################################

class SimpleFlaskModelServer(object):
  """
  Simple engine that allows the quick'n'dirty operationalization of a model. Ok,
  maybe not that dirty ;)
  
  Initialization:

    `model` : tf/keras trained/loaded model or a class that has a `.predict` function
    
    `fun_input`  : callback that receives a specific input json and transforms into a
                 model input. Ideally the input json should also contain a `'client'` 
                 key and value
                
    `fun_output` : callback that receives model output and prepares output json
    
    `log`  : mandatory Logger object
    
    `host` : host default to local
    
    `port` : port default 5000
  
  """
  app = None
  def __init__(self, model, log, 
               fun_input=None, fun_output=None, 
               host='127.0.0.1', port=5000, predict_function='predict',
               db_file=None, signup=True):
    self.__version__ = __VER__
    self.__name__ = 'MSRV'
    self.host = host
    self.port = port
    self.log = log
    self.model = model
    self.predict_function_name = predict_function
    self.authentication = db_file is not None
    
    if fun_input is None:
      fun_input = lambda x:x
      self.log.P("Using identity input [fun_input]")
    if fun_output is None:
      fun_output = lambda x:x
      self.log.P("Using identity output [fun_output]")

    self.fun_input = fun_input
    self.fun_output = fun_output
    self.app = flask.Flask('SimpleFlaskModelServer')
    self.create_analyze_endpoint()

    str_auth_log = '(without auth)'
    if self.authentication:
      self.app.config['SECRET_KEY'] = 'Lumm3tryFTW!'
      self.app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:////{}'.format(db_file)
      self.app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = True

      from flask_sqlalchemy import SQLAlchemy
      self.db = SQLAlchemy(self.app)
      db = self.db

      class Users(db.Model):
        id = db.Column(db.Integer, primary_key=True)
        public_id = db.Column(db.Integer)
        name = db.Column(db.String(50))
        password = db.Column(db.String(50))
        admin = db.Column(db.Boolean)

      self.Users = Users
      if signup:
        self.create_signup_user_endpoint()
      #endif
      self.create_login_user_endpoint()
      str_auth_log = '(with auth)'
    #endif

    _logo = "SimpleFlaskModelServer v{} {} started on '{}:{}'".format(
        self.__version__, str_auth_log, host, port)

    lead = 5
    _logo = " " * lead + _logo
    s2 = (len(_logo) + lead) * "*"
    self.log.P("")
    self.log.P(s2)
    self.log.P("")
    self.log.P(_logo)
    self.log.P("")
    self.log.P(s2)
    self.log.P("")


  def run(self):
    self.app.run(host=self.host, port=self.port)


  def create_signup_user_endpoint(self):
    import uuid
    from werkzeug.security import generate_password_hash

    def endp():
      data = flask.request.get_json()

      hashed_password = generate_password_hash(data['password'], method='sha256')

      new_user = self.Users(public_id=str(uuid.uuid4()), name=data['user'], password=hashed_password, admin=False)
      self.db.session.add(new_user)
      self.db.session.commit()
      return flask.jsonify({'message': 'registered successfully'})

    self.app.add_url_rule(rule='/signup',
                          endpoint='SignupEndpoint',
                          view_func=endp,
                          methods=['GET', 'POST'])


  def create_login_user_endpoint(self):
    from werkzeug.security import check_password_hash

    def endp():
      auth = flask.request.authorization

      if not auth or not auth.username or not auth.password:
        return flask.make_response('could not verify', 401, {'WWW.Authentication': 'Basic realm: "login required"'})

      user = self.Users.query.filter_by(name=auth.username).first()

      if check_password_hash(user.password, auth.password):
        token = jwt.encode(
          {'public_id': user.public_id, 'exp': datetime.datetime.utcnow() + datetime.timedelta(minutes=30)},
          self.app.config['SECRET_KEY'])
        return flask.jsonify({'token': token.decode('UTF-8')})

      return flask.make_response('could not verify', 401, {'WWW.Authentication': 'Basic realm: "login required"'})

    self.app.add_url_rule(rule='/login',
                          endpoint='LoginEndpoint',
                          view_func=endp,
                          methods=['GET', 'POST'])


  def create_analyze_endpoint(self):
   
    self.session = None
    self.graph = None
      
    self.model_server = NonReentrantModelServer(model=self.model, 
                                                model_fun=self.predict_function_name,
                                                session=self.session,
                                                graph=self.graph,
                                                fun_input=self.fun_input,
                                                fun_output=self.fun_output,
                                                log=self.log)
    endp = SimpleFlaskModelServer.AnalyzeEndpoint(outer=self,
                                                  model_server=self.model_server)
    if self.authentication:
      view_func = endp.call_with_token_required
    else:
      view_func = endp.call_without_token_required
    self.app.add_url_rule(rule='/analyze', 
                          endpoint="ModelEndpoint", 
                          view_func=view_func,
                          methods = ['GET', 'POST','OPTIONS']
                          )

  ###############################################################################

  class AnalyzeEndpoint(object):
    def __init__(self, outer, model_server):
      self.outer = outer
      self.model_server = model_server
      self.counter = 0
      return

    def wait_predict(self, data, client_id=None, wait_time=0):
      if self.model_server.in_use:
        print("  Model server in use. '{}' waiting...".format(client_id))
        while self.model_server.in_use:
          sleep(1)
        print("  Waiting done for '{}'".format(client_id))
      answer = self.model_server.predict(data,
                                         client_id=client_id,
                                         wait_time=wait_time)
      return answer

    def token_required(f):
      @wraps(f)
      def decorator(self, *args, **kwargs):
        token = None

        if 'x-access-tokens' in flask.request.headers:
          token = flask.request.headers['x-access-tokens']

        if not token:
          return flask.jsonify({'message': 'a valid token is missing'})

        try:
          _ = jwt.decode(token, self.outer.app.config['SECRET_KEY'])
        except:
          return flask.jsonify({'message': 'token is invalid'})

        return f(self, *args, **kwargs)

      return decorator

    @token_required
    def call_with_token_required(self, *args):
      return self.call(*args)

    def call_without_token_required(self, *args):
      return self.call(*args)

    def call(self, *args):
      self.counter += 1
      ccall = self.counter
      request = flask.request
      method = flask.request.method
      args_data = request.args
      form_data = request.form
      json_data = request.json
      params = None
      client = 'unk'

      if method == 'GET':
        # parameters in URL
        base_params = args_data
      else:
        # parameters in form
        base_params = form_data
        if len(base_params) == 0:
          # params in json?
          base_params = json_data

      if base_params is not None:
        if 'client' in base_params.keys():
          client = base_params['client']
        params = dict(base_params)

      print("Received '{}' request {} from client '{}' params: {}".format(
        method, ccall, client, params))

      ######
      # print("args_data: ", args_data)
      # print("form_data: ", form_data)
      # print("json_data: ", json_data)
      ######

      if method != 'OPTIONS':
        answer = self.wait_predict(params, client_id=client, wait_time=0)
      else:
        print("Received OPTIONS request")
        answer = {}
      if answer is None:
        jresponse = flask.jsonify({
          "ERROR": "input json does not contain right info or other error has occured",
          "client": client,
          "call_id": ccall,
          "input": base_params,
          "server": self.model_server.version})
      else:
        answer['call_id'] = ccall
        answer['server'] = self.model_server.version
        jresponse = flask.jsonify(answer)

      jresponse.headers["Access-Control-Allow-Origin"] = "*"
      jresponse.headers["Access-Control-Allow-Methods"] = "POST, GET, OPTIONS, DELETE"
      jresponse.headers["Access-Control-Allow-Headers"] = "Content-Type"
      return jresponse

  ###############################################################################


if __name__ == '__main__':
  
  class FakeLogger:
    def __init__(self, **kwargs):
      return
    
    def P(self, s):
      print("LOG: " + s, flush=True)
  
  def inp_proc(data):
    if 'input_value' not in data.keys():
      print("ERROR: input json does not contain data")
      return None
    # get string
    s = data['input_value']     
    # create dict
    d = [chr(x) for x in range(5000)] 
    # tokenize
    t = [d.index(x) for x in s] 
    # batchfy
    np_t = np.array(t).reshape((1,-1))
    return np_t
  
  def out_proc(data):
    # create dict
    d = [chr(x) for x in range(5000)]
    # select first obs
    t = data[0]
    t = t.ravel().astype(int)
    # get string 
    c = [d[x] for x in t]    
    s = "".join(c)
    return {'output_value':str(s)}
  
  l = FakeLogger(lib_name='MSRVT', no_folders_no_save=True)
    
  # model gets a sequnce
  tf_inp = tf.keras.layers.Input((None,))
  # adds a value to each token in all sequences
  tf_x = tf.keras.layers.Lambda(lambda x: x+1)(tf_inp)
  # returns modified input
  m = tf.keras.models.Model(tf_inp, tf_x)
  


  
  class FakeModel(object):
    def __init__(self, logger, fn_model):
      self.logger = logger
      self.fn_model = fn_model
      self.version = '0.0.1'
    
    def predict(self, usr_input):
      self.logger.P('Predicting on usr_input: {}'.format(usr_input))
      
      res = '{}+1={} PREDICTED'.format(usr_input,int(usr_input)+1)
      
      return res
    
  dummy_model = FakeModel(l, 'path/to/model.h5')
    
  def dummy_inp_proc(data):
    if 'input_value' not in data.keys():
      print("ERROR: input json does not contain data")
      return None
  
    s = data['input_value']      

    return s
  
  def dummy_out_proc(data):
    return {'output_value':str(data)}
  
  dummy_server = SimpleFlaskModelServer(model=dummy_model, 
                               fun_input=dummy_inp_proc, 
                               fun_output=dummy_out_proc,
                               log=l,
                               port=5000)
  
  dummy_server.run()
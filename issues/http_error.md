# http errors should have framework

currently, several places are manually setting http error
codes and messages and exiting.  this is bound to lead to
inconsistency especially when apps are plugging in, so there
should be some framework to return an error. this way an site
can configure custom error pages eventually, or by default have
something nice and consistent looking

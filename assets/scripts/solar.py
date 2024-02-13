
# calculate the yearly energy yield for a given hardware configuration at a handful of sites listed below

import calendar
import pvlib
import matplotlib.pyplot as plt
import pandas as pd
from pvlib.pvsystem import PVSystem
from pvlib.location import Location
from pvlib.modelchain import ModelChain
from pvlib.temperature import TEMPERATURE_MODEL_PARAMETERS
from flask import Flask
from flask import request

app = Flask(__name__)
@app.route('/')

def solar():

    # inputs from map & configurator

        # system-wide data
            # location of system (lat/lng)
            # altitude of system
            # solar panel model name (or custom panel parameters)
            # inverter model name (or custom inverter parameters)

        # data for each roof segment
            # tilt of arrays (roof pitch)
            # azimuth of arrays (roof heading)
            # number of arrays
            # number of panels per array

    # url to access web api, w/ example url parameters
    # http://mforcella.pythonanywhere.com/
    # ?latitude=41.710403&longitude=-74.395075&altitude=340&segment_count=2&roof_pitch0=20&&roof_pitch1=30&roof_direction0=180&&roof_direction1=230
    # &modules_per_string0=4&&modules_per_string1=3&strings_per_inverter0=5&&strings_per_inverter1=3

    # creating a PV system using modules and inverters from the library
    # https://pvlib-python.readthedocs.io/en/stable/reference/generated/pvlib.pvsystem.retrieve_sam.html
    sandia_modules = pvlib.pvsystem.retrieve_sam('SandiaMod')
    # cec_modules = pvlib.pvsystem.retrieve_sam('CECMod')
    inverters = pvlib.pvsystem.retrieve_sam('cecinverter')
    # adr_inverters = pvlib.pvsystem.retrieve_sam('ADRInverter')
    module_parameters = sandia_modules['Canadian_Solar_CS5P_220M___2009_']
    inverter_parameters = inverters['ABB__MICRO_0_25_I_OUTD_US_208__208V_']

    # it should also be possible to create a PV system by entering custom module/inverter values
    # module_parameters = {'pdc0': 5000, 'gamma_pdc': -0.004}
    # inverter_parameters = {'pdc0': 5000, 'eta_inv_nom': 0.96}

    # getting the temperature model parameters for the PV system
    # https://pvlib-python.readthedocs.io/en/stable/reference/pv_modeling/temperature.html
    temperature_model_parameters = TEMPERATURE_MODEL_PARAMETERS['sapm']['open_rack_glass_glass']

    ret_val = {}
    energies = []
    arrays = []

    ret_val['module'] = module_parameters.name
    ret_val['inverter'] = inverter_parameters.name

    # getting url parameters if present; latitude, longitude, altitude
    latitude = 41.710403 if request.args.get('latitude') == None else float(request.args.get('latitude'))
    longitude = -74.395075 if request.args.get('longitude') == None else float(request.args.get('longitude'))
    altitude = 340 if request.args.get('altitude') == None else int(request.args.get('altitude'))

    # setting the location of the PV system with lat, lng, and altitude values
    location = Location(latitude, longitude, altitude=altitude)
    ret_val['location'] = {
        # 'name':name,
        'latitude':latitude,
        'longitude':longitude,
        'altitude':altitude
    }

    # get TMY for each location
    # Typical Meteorological Year - irradiation, temperature and wind speed
    # https://pvlib-python.readthedocs.io/en/stable/reference/generated/pvlib.iotools.get_pvgis_tmy.html
    tmy = pvlib.iotools.get_pvgis_tmy(latitude, longitude)[0]

    # separate TMY by month to display monthly energy outputs
    weathers = [[] for y in range(13)]
    for index, row in tmy.iterrows():
        index = pd.to_datetime(index).month
        # row.index.name = "utc_time"
        weathers[index].append(row)

    # calculate energies for each roof segments
    # roof segment data will be coming from the map and configurator pages
    # each roof segment will have values for: roof_pitch, roof_direction, modules_per_string, strings_per_inverter
    segment_count = 1 if request.args.get('segment_count') == None else int(request.args.get('segment_count'))
    ret_val['roof_segment_count'] = segment_count

    # run the PV system model for each roof segment
    for i in range(segment_count):

        # roof_pitch = Tilt angle of the module surface. Up=0, horizon=90.
        # roof_direction = Azimuth angle of the module surface. North=0, East=90, South=180, West=270.
        # the url parameters for the roof segments will be numbered, e.g. roof_pitch0, roof_direction0, roof_pitch1, roof_direction1, etc
        roof_length = 0 if request.args.get('roof_length'+str(i)) == None else float(request.args.get('roof_length'+str(i)))
        roof_width = 0 if request.args.get('roof_width'+str(i)) == None else float(request.args.get('roof_width'+str(i)))
        roof_pitch = 20 if request.args.get('roof_pitch'+str(i)) == None else float(request.args.get('roof_pitch'+str(i)))
        roof_direction = 180 if request.args.get('roof_direction'+str(i)) == None else float(request.args.get('roof_direction'+str(i)))
        modules_per_string = 3 if request.args.get('modules_per_string'+str(i)) == None else int(request.args.get('modules_per_string'+str(i)))
        strings_per_inverter = 3 if request.args.get('strings_per_inverter'+str(i)) == None else int(request.args.get('strings_per_inverter'+str(i)))

        system = PVSystem(
            module_parameters=module_parameters,
            inverter_parameters=inverter_parameters,
            temperature_model_parameters=temperature_model_parameters,
            surface_tilt=roof_pitch,
            surface_azimuth=roof_direction,
            modules_per_string=modules_per_string,
            strings_per_inverter=strings_per_inverter
        )

        arrays.append({
            'roof_segment_length':roof_length,
            'roof_segment_width':roof_width,
            'roof_segment_pitch':roof_pitch,
            'roof_segment_direction':roof_direction,
            'modules_per_string':modules_per_string,
            'strings_per_inverter':strings_per_inverter,
            'total_modules':strings_per_inverter*modules_per_string,
        })

        # you can also create a weather DataFrame statically
        # weather = pd.DataFrame([[1050, 1000, 100, 30, 5]],
        #                    columns=['ghi', 'dni', 'dhi', 'temp_air', 'wind_speed'],
        #                    index=[pd.Timestamp('20170401 1200', tz='US/Arizona')])

        # create and run the model for each month of weather data
        mc = ModelChain(system, location)
        for i in range(0,12):
            mc.run_model(pd.DataFrame(weathers[i+1]))
            # sum all monthly energy outputs from each roof segment
            if i < len(energies):
                energies[i] += mc.results.ac.sum()
            else:
                energies.append(mc.results.ac.sum())

    # set return values
    ret_val['roof_segments'] = arrays
    ret_val['energies'] = energies
    return ret_val


